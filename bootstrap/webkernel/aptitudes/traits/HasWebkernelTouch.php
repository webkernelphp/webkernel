<?php declare(strict_types=1);
namespace Webkernel\Traits;

/**
 * HasQuickTouch
 *
 * Add this trait to your User model to enable per-user persistence of the
 * Webkernel Touch position and favorites via the database instead of
 * localStorage, and to gate access to the component by role or policy.
 *
 * Usage:
 *   use Webkernel\Traits\HasQuickTouch;
 *
 *   class User extends Authenticatable
 *   {
 *       use HasQuickTouch;
 *   }
 *
 * Required migration (add to users table):
 *   $table->json('quick_touch_favorites')->nullable();
 *   $table->boolean('quick_touch_enabled')->default(true);
 */
trait HasQuickTouch
{
    /**
     * Whether this user should see the Webkernel Touch component.
     *
     * Override in the User model or via a policy to restrict access.
     */
    public function hasQuickTouchEnabled(): bool
    {
        if (isset($this->quick_touch_enabled)) {
            return (bool) $this->quick_touch_enabled;
        }

        return true;
    }

    /**
     * Return the list of favorite pages saved by this user.
     *
     * @return array<int, array{url: string, title: string}>
     */
    public function getQuickTouchFavorites(): array
    {
        $raw = $this->quick_touch_favorites;

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
    public function addQuickTouchFavorite(array $favorite): void
    {
        $favorites = $this->getQuickTouchFavorites();

        $exists = array_filter($favorites, fn ($f) => ($f['url'] ?? '') === ($favorite['url'] ?? ''));

        if (empty($exists)) {
            $favorites[] = [
                'url'   => $favorite['url']   ?? '',
                'title' => $favorite['title'] ?? '',
            ];

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
     * Clear all stored favorites for this user.
     */
    public function clearQuickTouchFavorites(): void
    {
        $this->update(['quick_touch_favorites' => []]);
    }
}
