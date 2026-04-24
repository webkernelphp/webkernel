# Webkernel Connectors Layer

The Connectors layer is the foundation for all external and internal integrations
in Webkernel. It enforces strict separation of concerns for reusability and
stability in air-gapped environments.

This layer powers the no-code automation, website builder, database builder and future
self-hosted CMS components. App owners shall integrate services without writing any code. 
The entire platform is designed to be self-hosted for free.

## Directory Structure and Namespace Rules

- contracts/          -> Webkernel\Connectors\Contracts\*
  Contains only interfaces.

- facades/            -> Webkernel\Connectors\*
  Contains only facade classes.

- traits/             -> Webkernel\Connectors\Traits\*
  Contains only traits named Has*.php (example: HasLogging.php, HasRetry.php).
  These provide reusable horizontal behaviour that every connector can use:
  logging, retry logic, rate limiting, auth header injection,
  request validation, error normalization, etc.

- src/                -> Domain namespaces (Webkernel\Communication\*, Webkernel\Payment\*, etc.)
  Contains only concrete classes.
  These implement the contracts from Contracts\ and use the Has* traits.
  This is where the actual heavy lifting lives.

## Key Design Principles

1. Webkernel\Connectors\* contains ONLY traits, facades and contracts.
   No concrete logic is allowed here.

2. Domain implementations live under their own top-level namespaces
   (Communication, Payment, Integration, Social, Productivity, etc.).
   They implement contracts and compose the Has* traits.

3. Payment connectors are 100 percent wrappers.
   They delegate to existing Laravel or Filament integrations where possible.

4. Traits enable the reusability that defines the Aptitudes layer.
   Any method that is common across connectors belongs in a Has* trait.

## Usage Example in a Concrete Class

```php
<?php declare(strict_types=1);

namespace Webkernel\Communication\Chat\Global;

use Webkernel\Connectors\Contracts\ClientInterface;
use Webkernel\Connectors\Traits\HasRetry;
use Webkernel\Connectors\Traits\HasLogging;

final class Discord implements ClientInterface
{
    use HasRetry;
    use HasLogging;

    // concrete implementation here
}
```
