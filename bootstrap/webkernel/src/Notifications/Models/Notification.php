<?php declare(strict_types=1);

namespace Webkernel\Notifications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Notification extends Model
{
    use HasUuids;

    protected $table = 'notifications';
    protected $guarded = [];
    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    public static function createForUser(string $userId, string $title, string $body, string $status): self
    {
        return static::create([
            'type' => 'Webkernel\Notifications\BackgroundTaskNotification',
            'notifiable_type' => 'Webkernel\Users\Models\User',
            'notifiable_id' => $userId,
            'data' => [
                'title' => $title,
                'body' => $body,
                'status' => $status,
                'icon' => $status === 'danger' ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle',
                'iconColor' => $status,
                'duration' => 'persistent',
                'actions' => [],
                'view' => null,
                'viewData' => [],
                'format' => 'filament',
            ],
        ]);
    }
}
