<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $userName;
    public $otp;
    public $otpCreatedAt;
    public $type;

    /**
     * Create a new message instance.
     */
    public function __construct($userName, $otp, $otpCreatedAt, $type)
    {
        $this->userName = $userName;
        $this->otp = $otp;
        $this->otpCreatedAt = $otpCreatedAt;
        $this->type = $type;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'OTP Mail',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // return new Content(
        //     view: 'mail.otp',
        // );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    public function build()
    {
        $subject = '';

        switch ($this->type) {
            case 'register':
                $subject = 'Your OTP for Account Verification';
                break;
            case 'resendOtp':
                $subject = 'OTP Mail';
                break;
            case 'forgotPassword':
                $subject = 'Your OTP for Password Reset';
                break;
            default:
                $subject = 'OTP Mail';
                break;
        }

        return $this->subject($subject)->view('mail.otp');
    }
}
