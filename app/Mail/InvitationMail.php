<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Invitation $invitation, public Company $company, public string $acceptUrl) {}

    public function envelope(): Envelope
    {
        $from = config('mail.from.address');
        $reply = $this->company->email ?: $from;

        return new Envelope(
            from: new Address($from, $this->company->name ?: config('mail.from.name')),
            replyTo: [new Address($reply, $this->company->name)],
            subject: 'You are invited to '.$this->company->name,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.invitation');
    }
}
