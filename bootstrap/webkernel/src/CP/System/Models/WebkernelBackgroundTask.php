<?php declare(strict_types=1);

namespace Webkernel\CP\System\Models;

use Webkernel\Users\Models\User;
use Webkernel\Notifications\Models\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
class WebkernelBackgroundTask extends Model
{
    protected $connection = 'webkernel_sqlite';
    protected $table = 'inst_webkernel_background_tasks';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'type', 'label', 'payload', 'status', 'output', 'error', 'started_at', 'completed_at', 'user_id', 'suggested_action',
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
                $model->id = Str::ulid();
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

    public function markFailed(string $error, ?string $suggestedAction = null): void
    {
        // Detect common issues and provide actionable suggestions
        if (!$suggestedAction) {
            $suggestedAction = $this->detectSuggestedAction($error);
        }

        $this->update([
            'status' => 'failed',
            'error' => $error,
            'suggested_action' => $suggestedAction,
            'completed_at' => now(),
        ]);

        $this->notifyCompletion();
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
        return (int) $this->started_at->diffInSeconds($end);
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

    private function detectSuggestedAction(string $error): string
    {
        if (str_contains($error, 'could not be resolved')) {
            return 'Dependency conflict detected. Try: Update all packages together, or resolve conflicts manually in composer.json and retry.';
        }

        if (str_contains($error, 'does not exist')) {
            return 'Package not found. Verify the package name and version exist on Packagist.';
        }

        if (str_contains($error, 'No matching package')) {
            return 'Version constraint not satisfied. Try updating all packages or using a different version.';
        }

        return 'Check the error details above and try again, or contact support if the issue persists.';
    }

    private function notifyCompletion(): void
    {
        if (!$this->user_id) {
            return;
        }

        $title = $this->status === 'failed' ? "Task Failed: {$this->label}" : "Task Completed: {$this->label}";

        Notification::createForUser(
            userId: $this->user_id,
            title: $title,
            body: $this->suggested_action ?? $this->output,
            status: $this->status === 'failed' ? 'danger' : 'success',
        );
    }
}
