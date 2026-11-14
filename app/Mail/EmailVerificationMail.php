<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailVerificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $code;
    public $userName;

    /**
     * Create a new message instance.
     *
     * @param string|int $code
     * @param string|null $userName
     * @return void
     */
    public function __construct($code, $userName = null)
    {
        $this->code = $code;
        $this->userName = $userName;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Verify Your Email - Reparametrize')
            ->view('emails.verification-code')
            ->with([
                'code' => $this->code,
                'userName' => $this->userName,
            ]);
    }
}
