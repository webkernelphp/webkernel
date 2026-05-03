<?php declare(strict_types=1);

namespace Webkernel\Traits;

/**
 * HasQuickTouch
 *
 * Per-user persistence of QuickTouch favorites (and optionally the floating
 * button position) via the database.
 *
 * ── Required migration columns ───────────────────────────────────────────────
 *
 *   $table->boolean('quick_touch_enabled')->default(true);
 *   $table->json('quick_touch_favorites')->nullable();
 *
 * ── Optional: server-side position persistence ────────────────────────────────
 *
 * By default the button position is stored in localStorage (client-side).
 * Add the column below and call saveQuickTouchPosition() from a Livewire /
 * AJAX endpoint if you want server-side persistence:
 *
 *   $table->json('quick_touch_position')->nullable();
 *
 * ── Usage ─────────────────────────────────────────────────────────────────────
 *
 *   use Webkernel\Traits\HasQuickTouch;
 *
 *   class User extends Authenticatable
 *   {
 *       use HasQuickTouch;
 *   }
 *
 * ── Assumed model columns (already cast in the User model) ───────────────────
 *
 *   quick_touch_enabled   → cast: 'boolean'
 *   quick_touch_favorites → cast: 'array'
 *
 * @property bool                                                $quick_touch_enabled
 * @property array<int, array{url: string, title: string}>|null $quick_touch_favorites
 */
trait HasQuickTouch
{
    // ── Gate ──────────────────────────────────────────────────────────────────

    /**
     * Whether this user should see the QuickTouch component.
     *
     * Override in the User model (or via a Gate / policy) to restrict access.
     * Example: return $this->isAppOwner() || $this->isSuperUser();
     */
    public function hasQuickTouchEnabled(): bool
    {
        return isset($this->quick_touch_enabled)
            ? (bool) $this->quick_touch_enabled
            : true;
    }

    // ── Favorites ─────────────────────────────────────────────────────────────

    /**
     * Return the list of favorite pages saved by this user.
     *
     * @return list<array{url: string, title: string}>
     */
    public function getQuickTouchFavorites(): array
    {
        $raw = $this->quick_touch_favorites ?? null;

        // Guard: the model casts the column to 'array', but we handle the
        // string case defensively for callers that bypass the cast.
        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }

        return is_array($raw) ? array_values($raw) : [];
    }

    /**
     * Add a new favorite page for this user.
     *
     * Duplicate URLs (exact match) are silently ignored.
     *
     * @param  array{url: string, title: string}  $favorite
     */
    public function addQuickTouchFavorite(array $favorite): void
    {
        $url   = (string) ($favorite['url']   ?? '');
        $title = (string) ($favorite['title'] ?? '');

        if ($url === '') {
            return;
        }

        $favorites = $this->getQuickTouchFavorites();

        $exists = array_filter($favorites, static fn (array $f): bool => ($f['url'] ?? '') === $url);

        if (empty($exists)) {
            $favorites[] = ['url' => $url, 'title' => $title];
            $this->update(['quick_touch_favorites' => $favorites]);
        }
    }

    /**
     * Remove a favorite by its URL.
     */
    public function removeQuickTouchFavorite(string $url): void
    {
        $filtered = array_values(
            array_filter(
                $this->getQuickTouchFavorites(),
                static fn (array $f): bool => ($f['url'] ?? '') !== $url,
            )
        );

        $this->update(['quick_touch_favorites' => $filtered]);
    }

    /**
     * Replace all stored favorites at once.
     *
     * @param  list<array{url: string, title: string}>  $favorites
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

    // ── Optional: server-side position ───────────────────────────────────────

    /**
     * Save the floating button position to the database.
     *
     * Requires the migration column:
     *   $table->json('quick_touch_position')->nullable();
     *
     * @param  array{x: int|float, y: int|float}  $position
     */
    public function saveQuickTouchPosition(array $position): void
    {
        if (property_exists($this, 'quick_touch_position') || isset($this->quick_touch_position)) {
            $this->update([
                'quick_touch_position' => [
                    'x' => $position['x'],
                    'y' => $position['y'],
                ],
            ]);
        }
    }

    /**
     * Retrieve the last saved button position, or null when not persisted.
     *
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
