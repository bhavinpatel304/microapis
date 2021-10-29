<?php
namespace App\Mail;
 
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
 
class VerifyDocument extends Mailable {
 
    use Queueable,
        SerializesModels;
    public $user;

    public function __construct($user){
        $this->user = $user;
    }
 
    //build the message.
    public function build() {
        return $this->view('mail.verify-document');
    }
}