<?php

namespace App\Http\Controllers;

use App\Mail\DocumentMail;
use App\Models\EmailAttachment;
use App\Models\EmailMessage;
use App\Support\Audit;
use App\Support\ImapInbox;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class EmailController extends Controller
{
    public function index(Request $request)
    {
        $direction = $request->get('direction');
        $status = $request->get('status');
        $reference = $request->get('reference');
        $q = $request->get('q');

        $emails = EmailMessage::query()
            ->when($direction, fn ($query) => $query->where('direction', $direction))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($reference, fn ($query) => $query->where('reference_type', $reference))
            ->when($q, fn ($query) => $query->where(function ($w) use ($q) {
                $w->where('subject', 'like', "%$q%")
                    ->orWhere('to_email', 'like', "%$q%")
                    ->orWhere('from_email', 'like', "%$q%")
                    ->orWhere('from_name', 'like', "%$q%");
            }))
            ->latest('sent_at')
            ->paginate(25)
            ->withQueryString();

        $unread = EmailMessage::where('direction', 'incoming')->where('status', 'received')->count();

        return view('emails.index', compact('emails', 'direction', 'status', 'reference', 'q', 'unread'));
    }

    public function show(EmailMessage $emailMessage)
    {
        $emailMessage->load('files', 'sender');
        $emailMessage->markRead();
        $thread = $emailMessage->thread();

        return view('emails.show', ['email' => $emailMessage, 'thread' => $thread]);
    }

    public function compose(Request $request)
    {
        $reply = null;
        if ($request->filled('reply_to')) {
            $reply = EmailMessage::find($request->integer('reply_to'));
        }

        return view('emails.compose', compact('reply'));
    }

    public function send(Request $request)
    {
        $data = $request->validate([
            'to' => 'required|email',
            'cc' => 'nullable|string',
            'bcc' => 'nullable|string',
            'subject' => 'required|string|max:500',
            'message' => 'required|string',
            'reply_to_id' => 'nullable|exists:emails,id',
        ]);
        $company = auth()->user()->company;
        $status = 'sent';
        $error = null;
        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';
        $messageId = Str::uuid().'@'.$host;
        $reply = ! empty($data['reply_to_id']) ? EmailMessage::find($data['reply_to_id']) : null;
        $cc = $this->splitAddresses($data['cc'] ?? null);
        $bcc = $this->splitAddresses($data['bcc'] ?? null);
        $inReplyTo = $reply?->message_id;

        try {
            $mailable = new DocumentMail(
                company: $company,
                emailSubject: $data['subject'],
                intro: $data['message'],
                docLabel: 'Message',
                docNumber: '',
                amountLabel: '',
                messageId: $messageId,
                inReplyTo: $inReplyTo,
                ccAddresses: $cc,
            );
            $pending = Mail::to($data['to']);
            if ($bcc) {
                $pending->bcc($bcc);
            }
            $pending->send($mailable);
        } catch (Throwable $e) {
            $status = 'failed';
            $error = $e->getMessage();
        }

        EmailMessage::create([
            'message_id' => $messageId,
            'in_reply_to' => $inReplyTo,
            'from_email' => config('mail.from.address'),
            'from_name' => $company->name,
            'to_email' => $data['to'],
            'cc_email' => $cc ? implode(', ', $cc) : null,
            'bcc_email' => $bcc ? implode(', ', $bcc) : null,
            'subject' => $data['subject'],
            'body_html' => $data['message'],
            'status' => $status,
            'error_message' => $error,
            'reference_type' => $reply->reference_type ?? 'general',
            'reference_id' => $reply->reference_id ?? null,
            'direction' => 'outgoing',
            'sent_by' => auth()->id(),
            'sent_at' => now(),
        ]);

        Audit::log('Email', 'General', $reply?->id, $status.' to '.$data['to']);

        if ($status !== 'sent') {
            return back()->withInput()->with('error', 'Email failed: '.$error);
        }

        return redirect()->route('emails.index')->with('success', 'Email sent to '.$data['to']);
    }

    public function sync(ImapInbox $inbox)
    {
        $result = $inbox->sync(auth()->user()->company);
        if (! $result['ok']) {
            return back()->with('error', 'Inbox sync failed: '.$result['error']);
        }
        if ($result['synced'] === 0) {
            return back()->with('success', 'No new incoming messages.');
        }

        return back()->with('success', 'Fetched '.$result['synced'].' incoming message(s).');
    }

    public function attachment(EmailMessage $emailMessage, EmailAttachment $attachment)
    {
        abort_unless($attachment->email_id === $emailMessage->id, 404);
        abort_unless(Storage::disk('local')->exists($attachment->filepath), 404);

        return Storage::disk('local')->download($attachment->filepath, $attachment->filename);
    }

    protected function splitAddresses(?string $value): array
    {
        if (! $value) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $value)), fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL)));
    }
}
