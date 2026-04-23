<?php declare(strict_types=1);

namespace Webkernel\BackOffice\System\Config;

final class MarketplaceModules
{
    public static function all(): array
    {
        return [
            [
                'id' => 'wk-auth-v1',
                'name' => 'Authentication Module',
                'slug' => 'auth',
                'vendor' => 'webkernel',
                'description' => 'Complete authentication system with OAuth2, JWT, and session support.',
                'author' => 'Webkernel Team',
                'version' => '1.0.0',
                'released_at' => '2026-04-01',
                'downloads' => 1240,
                'rating' => 4.8,
                'tags' => ['auth', 'security', 'oauth2'],
            ],
            [
                'id' => 'wk-payments-v1',
                'name' => 'Payments Module',
                'slug' => 'payments',
                'vendor' => 'webkernel',
                'description' => 'Multi-provider payment processing with Stripe, PayPal, and local gateway support.',
                'author' => 'Webkernel Team',
                'version' => '1.1.0',
                'released_at' => '2026-03-20',
                'downloads' => 890,
                'rating' => 4.6,
                'tags' => ['payments', 'stripe', 'paypal'],
            ],
            [
                'id' => 'wk-notifications-v1',
                'name' => 'Notifications Module',
                'slug' => 'notifications',
                'vendor' => 'webkernel',
                'description' => 'Email, SMS, push notifications and in-app messaging in one module.',
                'author' => 'Webkernel Team',
                'version' => '2.0.0',
                'released_at' => '2026-02-15',
                'downloads' => 2150,
                'rating' => 4.9,
                'tags' => ['notifications', 'email', 'sms'],
            ],
        ];
    }
}
