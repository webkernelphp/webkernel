<?php

declare(strict_types=1);

namespace Webkernel\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class BackgroundTaskNotification extends Notification
{
    public function __construct(
        public string $title,
        public string $body,
        public string $status,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'title' => $this->title,
            'body' => $this->body,
            'status' => $this->status,
        ]);
    }
}
