<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\Video;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class LikeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User  $liker,
        public Video $video,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'       => 'like',
            'user_id'    => $this->liker->id,
            'username'   => $this->liker->username,
            'avatar'     => $this->liker->avatar,
            'video_id'   => $this->video->id,
            'thumbnail'  => $this->video->thumbnail_url,
            'message'    => "@{$this->liker->username} lájkolta a videódat",
        ];
    }

    public function toBroadcast(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
