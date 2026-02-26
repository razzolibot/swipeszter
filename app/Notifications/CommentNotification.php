<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class CommentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User    $commenter,
        public Comment $comment,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'       => 'comment',
            'user_id'    => $this->commenter->id,
            'username'   => $this->commenter->username,
            'avatar'     => $this->commenter->avatar,
            'video_id'   => $this->comment->video_id,
            'thumbnail'  => $this->comment->video->thumbnail_url,
            'message'    => "@{$this->commenter->username} hozzászólt: \"{$this->excerpt()}\"",
        ];
    }

    public function toBroadcast(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }

    private function excerpt(): string
    {
        return mb_strlen($this->comment->content) > 40
            ? mb_substr($this->comment->content, 0, 40) . '…'
            : $this->comment->content;
    }
}
