<?php
namespace App\Mail;
 
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
 
class RejectDocument extends Mailable {
 
    use Queueable,
        SerializesModels;
    public $user;
    public $reason;

    public function __construct($user, $reason){
        $this->user = $user;
        $this->reason = $reason;
    }
 
    //build the message.
    public function build() {
        return $this->view('mail.reject-document');
    }
}