<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMessageReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ContactMessage $contactMessage,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'رسالة تواصل جديدة - صنعة',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: sprintf(
                '<p>رسالة جديدة من نموذج "تواصل معنا":</p>
                <p><strong>الاسم:</strong> %s</p>
                <p><strong>المدينة:</strong> %s</p>
                <p><strong>البريد الإلكتروني:</strong> %s</p>
                <p><strong>الموضوع:</strong> %s</p>
                <p><strong>الرسالة:</strong></p>
                <p>%s</p>',
                e($this->contactMessage->full_name),
                e($this->contactMessage->city),
                e($this->contactMessage->email),
                e($this->contactMessage->subject),
                nl2br(e($this->contactMessage->message))
            ),
        );
    }
}
