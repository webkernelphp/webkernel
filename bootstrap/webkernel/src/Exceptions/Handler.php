<?php declare(strict_types=1);

namespace Webkernel\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as BaseHandler;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Bootstrap-safe exception handler.
 *
 * Avoids resolving the view factory before providers are registered.
 */
final class Handler extends BaseHandler
{
    protected function getHttpExceptionView(HttpExceptionInterface $e)
    {
        if (! $this->container->bound('view')) {
            return null;
        }

        return parent::getHttpExceptionView($e);
    }
}
