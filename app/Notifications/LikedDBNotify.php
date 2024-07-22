<?php

namespace App\Notifications;

use App\Models\Like;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class LikedDBNotify extends Notification
{
    use Queueable;
    private $likes,$operation;

    /**
     * Create a new notification instance.
     */
    public function __construct(Like $likes,$operation)
    {
        $this->likes=$likes;
        $this->operation= $operation;

    }


    public function via( $notifiable)
    {
        return ['database'];
    }

    public function toDataBase($notifiable)
    {
        $message='';
        if ($this->operation =='liked'){
            $message ="a aime ce publication";
        }



        return [
            "publication"=> $this->likes->post_id,
            "title"=> $message,
            "description" =>  $this->likes->post->description,
            'user'=>Auth::user()->name,
            'image'=>Auth::user()->image,
            'operation' => $this->operation,


        ];
    }
    
}
