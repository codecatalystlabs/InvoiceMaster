<?php

namespace App\Support;

use App\Models\Company;
use App\Models\EmailAttachment;
use App\Models\EmailMessage;
use Illuminate\Support\Facades\Storage;
use Throwable;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\IMAP;
use Webklex\PHPIMAP\Message;

class ImapInbox
{
    public function enabled(): bool
    {
        return (bool) config('imap.enabled')
            && filled(config('imap.host'))
            && filled(config('imap.username'));
    }

    /**
     * @return array{ok: bool, synced: int, skipped: int, error: ?string}
     */
    public function sync(?Company $company = null, ?int $days = null): array
    {
        if (! $this->enabled()) {
            return ['ok' => false, 'synced' => 0, 'skipped' => 0, 'error' => 'IMAP is not enabled or not configured.'];
        }

        $company ??= Company::query()->first();
        if (! $company) {
            return ['ok' => false, 'synced' => 0, 'skipped' => 0, 'error' => 'No company found to attach incoming mail.'];
        }

        try {
            $client = (new ClientManager)->make([
                'host' => config('imap.host'),
                'port' => config('imap.port'),
                'encryption' => config('imap.encryption'),
                'validate_cert' => (bool) config('imap.validate_cert'),
                'username' => config('imap.username'),
                'password' => config('imap.password'),
                'protocol' => 'imap',
                'timeout' => 30,
            ]);
            $client->connect();
            $folder = $client->getFolder(config('imap.folder', 'INBOX'));
            if (! $folder) {
                return ['ok' => false, 'synced' => 0, 'skipped' => 0, 'error' => 'IMAP folder not found.'];
            }

            $since = now()->subDays($days ?: (int) config('imap.days', 14));
            $messages = $folder->query()
                ->since($since)
                ->softFail()
                ->fetchOptions(IMAP::FT_PEEK)
                ->limit(150)
                ->get();

            $synced = 0;
            $skipped = 0;

            foreach ($messages as $message) {
                try {
                    if ($this->store($message, $company)) {
                        $synced++;
                    } else {
                        $skipped++;
                    }
                } catch (Throwable) {
                    $skipped++;
                }
            }

            $client->disconnect();

            return ['ok' => true, 'synced' => $synced, 'skipped' => $skipped, 'error' => null];
        } catch (Throwable $e) {
            $detail = $e->getMessage();
            if ($e->getPrevious()) {
                $detail .= ' ('.$e->getPrevious()->getMessage().')';
            }

            return ['ok' => false, 'synced' => 0, 'skipped' => 0, 'error' => $detail];
        }
    }

    protected function store(Message $message, Company $company): bool
    {
        $messageId = $this->cleanId($this->attr($message->getMessageId()));
        if ($messageId && EmailMessage::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('message_id', $messageId)
            ->exists()) {
            return false;
        }

        $from = $this->firstAddress($message->getFrom());
        $to = $this->addressList($message->getTo());
        $cc = $this->addressList($message->getCc());
        $inReplyTo = $this->cleanId($this->attr($message->getInReplyTo()));
        $subject = $this->attr($message->getSubject()) ?: '(No Subject)';
        $html = (string) $message->getHTMLBody();
        $text = (string) $message->getTextBody();
        if ($html === '' && $text !== '') {
            $html = nl2br(e($text));
        }

        $date = $message->getDate()?->first();
        $sentAt = $date ? now()->parse((string) $date) : now();
        if (! $messageId) {
            $uid = method_exists($message, 'getUid') ? (string) $message->getUid() : uniqid('', true);
            $messageId = 'imap-'.$uid.'-'.sha1($from['mail'].'|'.$subject.'|'.$sentAt->toIso8601String());
        }
        if (EmailMessage::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('message_id', $messageId)
            ->exists()) {
            return false;
        }

        $referenceType = 'general';
        $referenceId = null;
        if ($inReplyTo) {
            $parent = EmailMessage::withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('message_id', $inReplyTo)
                ->first();
            if ($parent) {
                $referenceType = $parent->reference_type ?: 'general';
                $referenceId = $parent->reference_id;
            }
        }

        $attachments = $message->getAttachments();
        $hasAttachment = $attachments->count() > 0;
        $firstName = $hasAttachment ? (string) $attachments->first()->getName() : null;

        $email = EmailMessage::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'message_id' => $messageId,
            'in_reply_to' => $inReplyTo,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'direction' => 'incoming',
            'from_email' => $from['mail'] ?: 'unknown@unknown',
            'from_name' => $from['name'] ?: null,
            'to_email' => $to ?: (string) config('imap.username'),
            'cc_email' => $cc ?: null,
            'subject' => mb_substr($subject, 0, 500),
            'body_html' => $html,
            'body_text' => $text ?: null,
            'has_attachment' => $hasAttachment,
            'attachment_name' => $firstName,
            'status' => 'received',
            'sent_at' => $sentAt,
            'received_at' => now(),
        ]);

        if ($hasAttachment) {
            foreach ($attachments as $attachment) {
                $filename = basename((string) ($attachment->getName() ?: 'attachment.bin'));
                $filename = preg_replace('/[^A-Za-z0-9._-]+/', '_', $filename) ?: 'attachment.bin';
                $path = 'email-attachments/'.$email->id.'/'.$filename;
                Storage::disk('local')->put($path, $attachment->getContent());
                EmailAttachment::create([
                    'email_id' => $email->id,
                    'filename' => $filename,
                    'filepath' => $path,
                    'filesize' => Storage::disk('local')->size($path),
                    'mime_type' => $attachment->getMimeType() ?: null,
                ]);
            }
        }

        return true;
    }

    protected function attr(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        if (is_string($value) || is_numeric($value)) {
            return trim((string) $value);
        }
        if (is_object($value) && method_exists($value, 'toString')) {
            return trim($value->toString());
        }
        if (is_object($value) && method_exists($value, 'first')) {
            $first = $value->first();

            return $this->attr($first);
        }

        return trim((string) $value);
    }

    protected function cleanId(string $id): ?string
    {
        $id = trim($id, " \t\n\r\0\x0B<>");

        return $id !== '' ? $id : null;
    }

    /**
     * @return array{mail: string, name: string}
     */
    protected function firstAddress(mixed $attr): array
    {
        $list = $this->addresses($attr);

        return $list[0] ?? ['mail' => '', 'name' => ''];
    }

    protected function addressList(mixed $attr): string
    {
        return implode(', ', array_filter(array_map(
            fn ($row) => $row['mail'],
            $this->addresses($attr)
        )));
    }

    /**
     * @return array<int, array{mail: string, name: string}>
     */
    protected function addresses(mixed $attr): array
    {
        if ($attr === null) {
            return [];
        }
        $items = [];
        if (is_object($attr) && method_exists($attr, 'all')) {
            $items = $attr->all();
        } elseif (is_iterable($attr)) {
            $items = $attr;
        } else {
            $items = [$attr];
        }
        $out = [];
        foreach ($items as $item) {
            if (is_object($item)) {
                $out[] = [
                    'mail' => (string) ($item->mail ?? $item->mailbox ?? ''),
                    'name' => (string) ($item->personal ?? $item->full ?? ''),
                ];
            } elseif (is_string($item) && $item !== '') {
                $out[] = ['mail' => $item, 'name' => ''];
            }
        }

        return $out;
    }
}
