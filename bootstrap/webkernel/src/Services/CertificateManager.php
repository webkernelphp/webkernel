<?php declare(strict_types=1);

namespace Webkernel\Services;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Webkernel\Domains\Models\Domain;

/**
 * CertificateManager — manages TLS certificates via certbot.
 *
 * Uses Symfony Process (not shell_exec) for safe subprocess execution.
 * All certbot calls are non-interactive and use standalone mode.
 *
 * Wildcard certs cover all subdomains of the root domain automatically.
 * Per-domain certs are issued for custom domains (A record or NS delegation).
 */
class CertificateManager
{
    public function issueWildcardCert(string $rootDomain): void
    {
        $process = new Process([
            'certbot', 'certonly',
            '--standalone',
            '--non-interactive',
            '--agree-tos',
            '--email', config('app.admin_email', config('mail.from.address')),
            '-d', $rootDomain,
            '-d', "*.{$rootDomain}",
        ]);

        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    public function issuePerDomainCert(string $domain): void
    {
        $process = new Process([
            'certbot', 'certonly',
            '--standalone',
            '--non-interactive',
            '--agree-tos',
            '--email', config('app.admin_email', config('mail.from.address')),
            '-d', $domain,
        ]);

        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $this->updateDomainSslRecord($domain);
    }

    public function renewAll(): void
    {
        $process = new Process(['certbot', 'renew', '--quiet']);
        $process->setTimeout(600);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    public function certExpiresAt(string $domain): ?\DateTimeImmutable
    {
        $certPath = $this->certPath($domain);

        if (! file_exists($certPath)) {
            return null;
        }

        $process = new Process(['openssl', 'x509', '-enddate', '-noout', '-in', $certPath]);
        $process->run();

        if (! $process->isSuccessful()) {
            return null;
        }

        // Output: "notAfter=Apr 24 12:00:00 2026 GMT"
        preg_match('/notAfter=(.+)/', trim($process->getOutput()), $matches);

        return isset($matches[1])
            ? \DateTimeImmutable::createFromFormat('M j H:i:s Y T', trim($matches[1])) ?: null
            : null;
    }

    private function certPath(string $domain): string
    {
        return "/etc/letsencrypt/live/{$domain}/cert.pem";
    }

    private function keyPath(string $domain): string
    {
        return "/etc/letsencrypt/live/{$domain}/privkey.pem";
    }

    private function updateDomainSslRecord(string $domainName): void
    {
        $domain = Domain::where('domain', $domainName)->first();

        if (! $domain) {
            return;
        }

        $domain->ssl_cert_path  = $this->certPath($domainName);
        $domain->ssl_key_path   = $this->keyPath($domainName);
        $domain->ssl_expires_at = $this->certExpiresAt($domainName);
        $domain->save();
    }
}
