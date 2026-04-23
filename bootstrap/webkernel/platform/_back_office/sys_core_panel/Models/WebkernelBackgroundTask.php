<?php declare(strict_types=1);

namespace Webkernel\BackOffice\System\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Model;

class WebkernelBackgroundTask extends Model
{
    protected $connection = 'webkernel_sqlite';
    protected $table = 'inst_webkernel_background_tasks';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'type', 'label', 'payload', 'status', 'output', 'error', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'payload' => AsCollection::class,
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public static function booting(): void
    {
        static::creating(function (self $model) {
            if (empty($model->id)) {
                $model->id = \Illuminate\Support\Str::ulid();
            }
        });
    }

    public function scopePending(Builder $q): Builder
    {
        return $q->where('status', 'pending');
    }

    public function scopeRunning(Builder $q): Builder
    {
        return $q->where('status', 'running');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->whereIn('status', ['pending', 'running']);
    }

    public function scopeCompleted(Builder $q): Builder
    {
        return $q->where('status', 'completed');
    }

    public function scopeFailed(Builder $q): Builder
    {
        return $q->where('status', 'failed');
    }

    public function scopeCancelled(Builder $q): Builder
    {
        return $q->where('status', 'cancelled');
    }

    public function markRunning(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    public function markCompleted(string $output = ''): void
    {
        $this->update([
            'status' => 'completed',
            'output' => $output,
            'completed_at' => now(),
        ]);
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error' => $error,
            'completed_at' => now(),
        ]);
    }

    public function markCancelled(): void
    {
        $this->update([
            'status' => 'cancelled',
            'completed_at' => now(),
        ]);
    }

    public function getDuration(): ?int
    {
        if (!$this->started_at) {
            return null;
        }

        $end = $this->completed_at ?? now();
        return $this->started_at->diffInSeconds($end);
    }

    public function getDurationFormatted(): string
    {
        $duration = $this->getDuration();
        if ($duration === null) {
            return '—';
        }

        if ($duration < 60) {
            return "{$duration}s";
        }

        $minutes = intdiv($duration, 60);
        $seconds = $duration % 60;

        if ($minutes < 60) {
            return "{$minutes}m {$seconds}s";
        }

        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        return "{$hours}h {$mins}m";
    }
}
