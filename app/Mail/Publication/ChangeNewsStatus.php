<?php
namespace App\Mail\Publication;
 
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
 
class ChangeNewsStatus extends Mailable {
 
    use Queueable,
        SerializesModels;
    public $data;

    public function __construct($data){
        $this->data = $data;
    }
 
    //build the message.
    public function build() {
        return $this->view('mail.publication.change-news-status',$this->data);
    }
}