<?php declare(strict_types=1);
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
if (!function_exists('http_request_back')) {
  /**
   * Smart Immutable Back Link Helper
   * Provides a fixed return point via high-entropy
   * tokens to avoid referer loops.
   */
  function http_request_back(?string $explicitOrigin = null): string
  {
    $prefix = '__back';
    $current = url()->current();

    // IGNORE Livewire/internal URLs - they're not real destinations
    if (
      str_contains($current, '/livewire') ||
      str_contains($current, '/livewire-update') ||
      str_contains($current, '/__back/')
    ) {
      // Return existing link without changing anything
      return Session::get('back_url') ?? url('/');
    }

    $destinationKey = md5($current);
    $storedDestinationKey = Session::get('back_destination_key');

    // SAME DESTINATION - Reuse everything
    if ($storedDestinationKey === $destinationKey) {
      return Session::get('back_url');
    }

    // NEW DESTINATION - Capture origin
    $token = Str::random(16);

    if ($explicitOrigin !== null) {
      $origin = $explicitOrigin;
    } else {
      $previous = url()->previous();
      if (
        empty($previous) ||
        $previous === url()->current() ||
        str_contains($previous, "/{$prefix}/") ||
        str_contains($previous, '/livewire')
      ) {
        $origin = url('/');
      } else {
        $origin = $previous;
      }
    }

    Session::put('back_origin', $origin);
    Session::put('back_url', url("{$prefix}/{$token}"));
    Session::put('back_destination_key', $destinationKey);
    Session::put("origin_{$token}", $origin);

    return Session::get('back_url');
  }
}
