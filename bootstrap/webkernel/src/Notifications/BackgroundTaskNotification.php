<?php

declare(strict_types=1);

namespace Webkernel\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        $iconMap = [
            'success' => 'heroicon-o-check-circle',
            'danger' => 'heroicon-o-x-circle',
            'warning' => 'heroicon-o-exclamation-circle',
            'info' => 'heroicon-o-information-circle',
        ];

        return new DatabaseMessage([
            'title' => $this->title,
            'body' => $this->body,
            'status' => $this->status,
            'icon' => $iconMap[$this->status] ?? 'heroicon-o-bell',
            'iconColor' => $this->status,
            'duration' => 'persistent',
            'actions' => [],
            'view' => null,
            'viewData' => [],
            'format' => 'filament',
        ]);
    }
}
