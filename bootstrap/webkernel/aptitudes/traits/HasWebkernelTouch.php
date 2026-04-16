<?php declare(strict_types=1);
namespace Webkernel\Traits;

/**
 * HasWebkernelTouch
 *
 * Add this trait to your User model to enable per-user persistence of the
 * Webkernel Touch position and favorites via the database instead of
 * localStorage, and to gate access to the component by role or policy.
 *
 * Usage:
 *   use Webkernel\Traits\HasWebkernelTouch;
 *
 *   class User extends Authenticatable
 *   {
 *       use HasWebkernelTouch;
 *   }
 *
 * Required migration (add to users table):
 *   $table->json('wkt_position')->nullable();
 *   $table->json('wkt_favorites')->nullable();
 *   $table->boolean('wkt_enabled')->default(true);
 */
trait HasWebkernelTouch
{
    /**
     * Whether this user should see the Webkernel Touch component.
     *
     * Override in the User model or via a policy to restrict access.
     */
    public function hasWebkernelTouchEnabled(): bool
    {
        if (isset($this->wkt_enabled)) {
            return (bool) $this->wkt_enabled;
        }

        return true;
    }

    /**
     * Persist the floating button position (called from a lightweight
     * Livewire component or a dedicated API endpoint).
     *
     * @param  array{x: int, y: int}  $position
     */
    public function saveWebkernelTouchPosition(array $position): void
    {
        $this->update(['wkt_position' => $position]);
    }

    /**
     * Retrieve the stored position for this user.
     *
     * @return array{x: int, y: int}|null
     */
    public function getWebkernelTouchPosition(): ?array
    {
        $raw = $this->wkt_position;

        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }

        if (is_array($raw) && isset($raw['x'], $raw['y'])) {
            return [
                'x' => (int) $raw['x'],
                'y' => (int) $raw['y'],
            ];
        }

        return null;
    }

    /**
     * Return the list of favorite pages saved by this user.
     *
     * @return array<int, array{url: string, title: string}>
     */
    public function getWebkernelTouchFavorites(): array
    {
        $raw = $this->wkt_favorites;

        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }

        return is_array($raw) ? $raw : [];
    }

    /**
     * Add a page to this user's favorites.
     *
     * @param  array{url: string, title: string}  $favorite
     */
    public function addWebkernelTouchFavorite(array $favorite): void
    {
        $favorites = $this->getWebkernelTouchFavorites();

        $exists = array_filter($favorites, fn ($f) => ($f['url'] ?? '') === ($favorite['url'] ?? ''));

        if (empty($exists)) {
            $favorites[] = [
                'url'   => $favorite['url']   ?? '',
                'title' => $favorite['title'] ?? '',
            ];

            $this->update(['wkt_favorites' => $favorites]);
        }
    }

    /**
     * Remove a favorite by its URL.
     */
    public function removeWebkernelTouchFavorite(string $url): void
    {
        $favorites = array_values(
            array_filter(
                $this->getWebkernelTouchFavorites(),
                fn ($f) => ($f['url'] ?? '') !== $url,
            )
        );

        $this->update(['wkt_favorites' => $favorites]);
    }

    /**
     * Clear all stored favorites for this user.
     */
    public function clearWebkernelTouchFavorites(): void
    {
        $this->update(['wkt_favorites' => []]);
    }
}
