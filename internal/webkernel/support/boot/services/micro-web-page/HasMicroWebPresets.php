<?php declare(strict_types=1);
// ═══════════════════════════════════════════════════════════════════
//  Trait: HasMicroWebPresets
//  Semantic page presets for common error and info states.
//  All methods return static so you can keep chaining.
// ═══════════════════════════════════════════════════════════════════

trait HasMicroWebPresets
{
    // ── Error presets ─────────────────────────────────────────────────────

    public function validationFailed(string $detail, int $code = 422): static
    {
        return $this->title('Validation Failed')
                    ->message($detail)
                    ->severity('WARNING')
                    ->code($code);
    }

    public function accessBlocked(string $reason, string $supportHref = ''): static
    {
        $b = $this->title('Access Blocked')
                  ->message($reason)
                  ->severity('WARNING')
                  ->code(403);
        if ($supportHref !== '') {
            $b->addButton('Contact Support', $supportHref);
        }
        return $b;
    }

    public function rateLimited(string $detail = 'Please slow down and try again shortly.'): static
    {
        return $this->title('Too Many Requests')
                    ->message($detail)
                    ->severity('WARNING')
                    ->code(429)
                    ->footer('RATE LIMIT REACHED');
    }

    public function maintenance(string $detail = 'The system is being updated. Please check back shortly.'): static
    {
        return $this->title('Scheduled Maintenance')
                    ->message($detail)
                    ->severity('INFO')
                    ->code(503);
    }

    public function notFound(string $detail = 'The page you are looking for could not be found.'): static
    {
        return $this->title('Page Not Found')
                    ->message($detail)
                    ->severity('WARNING')
                    ->code(404);
    }

    public function serverError(string $detail = 'An unexpected server error occurred.'): static
    {
        return $this->title('Server Error')
                    ->message($detail)
                    ->severity('CRITICAL')
                    ->code(500);
    }

    public function forbidden(string $detail = 'You do not have permission to access this resource.'): static
    {
        return $this->title('Forbidden')
                    ->message($detail)
                    ->severity('WARNING')
                    ->code(403);
    }

    public function serviceUnavailable(string $detail = 'Service is temporarily unavailable.'): static
    {
        return $this->title('Service Unavailable')
                    ->message($detail)
                    ->severity('WARNING')
                    ->code(503);
    }

    // ── Setup / info presets ─────────────────────────────────────────────

    public function setupPage(string $title = 'First-run Setup', string $message = ''): static
    {
        return $this->title($title)
                    ->message($message)
                    ->severity('SETUP')
                    ->code(200)
                    ->systemState('FIRST-RUN SETUP');
    }

    public function infoPage(string $title, string $message = ''): static
    {
        return $this->title($title)
                    ->message($message)
                    ->severity('INFO')
                    ->code(200);
    }
}
