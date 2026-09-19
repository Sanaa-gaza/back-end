<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public string $purpose,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->purpose === 'reset'
                ? 'رمز استعادة كلمة المرور - صنعة'
                : 'رمز تأكيد البريد الإلكتروني - صنعة',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: sprintf(
                '<p>رمز التحقق الخاص بك هو: <strong>%s</strong></p><p>صالح لمدة 3 دقائق.</p>',
                $this->code
            ),
        );
    }
}
