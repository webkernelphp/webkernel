<?php declare(strict_types=1);

namespace Webkernel\System\Http;

final class HttpManager
{
    public function github(): GithubClient
    {
        return new GithubClient();
    }
}
