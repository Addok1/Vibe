<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserDisapproveMail extends Mailable
{
    use Queueable, SerializesModels;

    public $notificationData;
    public $user;

    /**
     * Create a new message instance.
     *
     * @param $driver
     * @param $notificationData
     */
    public function __construct($user, $notificationData)
    {
        $this->user = $user;
        $this->notificationData = $notificationData;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $app_name = get_settings('app_name');
        return $this->subject($this->notificationData['email_subject'])
                    ->view('emails.driverDisapprovedMessage',['app_name' => $app_name]) // Blade template for email
                    ->with([
                        'driver' => $this->user,
                        'notificationData' => $this->notificationData,
                    ]);
    }
}
