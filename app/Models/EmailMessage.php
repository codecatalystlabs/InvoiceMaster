<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailMessage extends Model
{
    use BelongsToCompany;

    protected $table = 'emails';

    protected $fillable = [
        'company_id', 'message_id', 'in_reply_to', 'reference_type', 'reference_id', 'direction',
        'from_email', 'from_name', 'to_email', 'cc_email', 'bcc_email', 'subject',
        'body_html', 'body_text', 'has_attachment', 'attachment_name', 'status',
        'sent_by', 'sent_at', 'received_at', 'read_at', 'error_message',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'received_at' => 'datetime',
            'read_at' => 'datetime',
            'has_attachment' => 'boolean',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function files(): HasMany
    {
        return $this->hasMany(EmailAttachment::class, 'email_id');
    }

    public function isIncoming(): bool
    {
        return $this->direction === 'incoming';
    }

    public function isUnread(): bool
    {
        return $this->isIncoming() && $this->status === 'received';
    }

    public function markRead(): void
    {
        if ($this->isUnread()) {
            $this->update(['status' => 'read', 'read_at' => now()]);
        }
    }

    public function displayParty(): string
    {
        if ($this->isIncoming()) {
            return $this->from_name ?: $this->from_email;
        }

        return $this->to_email;
    }

    public function safeHtml(): string
    {
        $html = $this->body_html ?: nl2br(e((string) $this->body_text));
        $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html) ?? $html;
        $html = preg_replace('#<iframe\b[^>]*>.*?</iframe>#is', '', $html) ?? $html;
        $html = preg_replace('#on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html) ?? $html;

        return $html;
    }

    public function thread()
    {
        if (! $this->message_id && ! $this->in_reply_to) {
            return collect();
        }

        return static::query()
            ->where('id', '!=', $this->id)
            ->where(function ($q) {
                if ($this->in_reply_to) {
                    $q->where('message_id', $this->in_reply_to)
                        ->orWhere('in_reply_to', $this->in_reply_to);
                }
                if ($this->message_id) {
                    $q->orWhere('in_reply_to', $this->message_id);
                }
            })
            ->orderBy('sent_at')
            ->get();
    }
}
