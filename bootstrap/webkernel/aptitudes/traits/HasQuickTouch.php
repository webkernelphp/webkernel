<?php declare(strict_types=1);

namespace Webkernel\Traits;

/**
 * HasQuickTouch
 *
 * Add this trait to your User model to enable per-user persistence of the
 * QuickTouch position and favorites via the database instead of localStorage,
 * and to gate access to the component by role or policy.
 *
 * ──────────────────────────────────────────────────────────────────────────
 * Usage
 * ──────────────────────────────────────────────────────────────────────────
 *
 *   use Webkernel\Traits\HasQuickTouch;
 *
 *   class User extends Authenticatable
 *   {
 *       use HasQuickTouch;
 *   }
 *
 * ──────────────────────────────────────────────────────────────────────────
 * Required migration
 * ──────────────────────────────────────────────────────────────────────────
 *
 *   $table->json('quick_touch_favorites')->nullable();
 *   $table->boolean('quick_touch_enabled')->default(true);
 *
 * ──────────────────────────────────────────────────────────────────────────
 * Optional: position persistence
 * ──────────────────────────────────────────────────────────────────────────
 *
 * By default the floating button position is stored in localStorage (client
 * side). If you want server-side persistence, add:
 *
 *   $table->json('quick_touch_position')->nullable();
 *
 * and call `$user->saveQuickTouchPosition(['x' => 100, 'y' => 200])` from a
 * dedicated AJAX/Livewire endpoint in your application.
 */
trait HasQuickTouch
{
    // ── gate ────────────────────────────────────────────────────────────────

    /**
     * Whether this user should see the QuickTouch component.
     *
     * Override in the User model or via a gate/policy to restrict access.
     * e.g. return $this->hasRole('admin');
     */
    public function hasQuickTouchEnabled(): bool
    {
        return isset($this->quick_touch_enabled)
            ? (bool) $this->quick_touch_enabled
            : true;
    }

    // ── favorites ───────────────────────────────────────────────────────────

    /**
     * Return the list of favorite pages saved by this user.
     *
     * @return array<int, array{url: string, title: string}>
     */
    public function getQuickTouchFavorites(): array
    {
        $raw = $this->quick_touch_favorites ?? null;

        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }

        return is_array($raw) ? array_values($raw) : [];
    }

    /**
     * Persist a new favorite page for this user.
     *
     * Duplicate URLs (by exact match) are silently ignored.
     *
     * @param  array{url: string, title: string}  $favorite
     */
    public function addQuickTouchFavorite(array $favorite): void
    {
        $url       = $favorite['url']   ?? '';
        $title     = $favorite['title'] ?? '';
        $favorites = $this->getQuickTouchFavorites();

        $exists = array_filter($favorites, fn ($f) => ($f['url'] ?? '') === $url);

        if (empty($exists)) {
            $favorites[] = compact('url', 'title');
            $this->update(['quick_touch_favorites' => $favorites]);
        }
    }

    /**
     * Remove a favorite by its URL.
     */
    public function removeQuickTouchFavorite(string $url): void
    {
        $favorites = array_values(
            array_filter(
                $this->getQuickTouchFavorites(),
                fn ($f) => ($f['url'] ?? '') !== $url,
            )
        );

        $this->update(['quick_touch_favorites' => $favorites]);
    }

    /**
     * Replace all favorites at once.
     *
     * @param  array<int, array{url: string, title: string}>  $favorites
     */
    public function setQuickTouchFavorites(array $favorites): void
    {
        $this->update(['quick_touch_favorites' => array_values($favorites)]);
    }

    /**
     * Remove all stored favorites for this user.
     */
    public function clearQuickTouchFavorites(): void
    {
        $this->update(['quick_touch_favorites' => []]);
    }

    // ── optional: server-side position ──────────────────────────────────────

    /**
     * Save the button position to the database.
     * Requires: $table->json('quick_touch_position')->nullable();
     *
     * @param  array{x: int|float, y: int|float}  $position
     */
    public function saveQuickTouchPosition(array $position): void
    {
        if (isset($this->quick_touch_position)) {
            $this->update(['quick_touch_position' => $position]);
        }
    }

    /**
     * @return array{x: float, y: float}|null
     */
    public function getQuickTouchPosition(): ?array
    {
        $raw = $this->quick_touch_position ?? null;

        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }

        if (is_array($raw) && isset($raw['x'], $raw['y'])) {
            return ['x' => (float) $raw['x'], 'y' => (float) $raw['y']];
        }

        return null;
    }
}
