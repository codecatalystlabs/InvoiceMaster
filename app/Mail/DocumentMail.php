<?php

namespace App\Mail;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class DocumentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Company $company,
        public string $emailSubject,
        public string $intro,
        public string $docLabel,
        public string $docNumber,
        public string $amountLabel,
        public ?string $pdfPath = null,
        public ?string $pdfName = null,
        public ?string $messageId = null,
        public ?string $inReplyTo = null,
        public array $ccAddresses = [],
    ) {}

    public function envelope(): Envelope
    {
        $from = config('mail.from.address');
        $reply = $this->company->email ?: $from;

        return new Envelope(
            from: new Address($from, $this->company->name ?: config('mail.from.name')),
            replyTo: [new Address($reply, $this->company->name)],
            cc: $this->ccAddresses,
            subject: $this->emailSubject,
        );
    }

    public function headers(): Headers
    {
        $inReply = $this->inReplyTo ? trim($this->inReplyTo, '<>') : null;

        return new Headers(
            messageId: $this->messageId,
            references: $inReply ? [$inReply] : [],
            text: $inReply ? ['In-Reply-To' => '<'.$inReply.'>'] : [],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.document');
    }

    public function attachments(): array
    {
        if (! $this->pdfPath || ! is_file($this->pdfPath)) {
            return [];
        }

        return [
            Attachment::fromPath($this->pdfPath)->as($this->pdfName ?: basename($this->pdfPath)),
        ];
    }
}
