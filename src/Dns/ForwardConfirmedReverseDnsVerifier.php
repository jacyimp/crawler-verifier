<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Dns;

final readonly class ForwardConfirmedReverseDnsVerifier
{
    public function __construct(
        private DnsResolver $resolver = new NativeDnsResolver(),
    ) {
    }

    /**
     * @param list<string> $allowedSuffixes
     */
    public function verify(
        string $ip,
        array $allowedSuffixes,
    ): bool {
        $ipBinary = inet_pton($ip);

        if ($ipBinary === false) {
            return false;
        }

        $hostname = $this->resolver->reverse($ip);

        if ($hostname === null) {
            return false;
        }

        $hostname = strtolower(rtrim($hostname, '.'));

        if (!$this->hasAllowedSuffix($hostname, $allowedSuffixes)) {
            return false;
        }

        foreach ($this->resolver->forward($hostname) as $resolvedIp) {
            $resolvedBinary = inet_pton($resolvedIp);

            if (
                $resolvedBinary !== false
                && $resolvedBinary === $ipBinary
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<string> $allowedSuffixes
     */
    private function hasAllowedSuffix(
        string $hostname,
        array $allowedSuffixes,
    ): bool {
        foreach ($allowedSuffixes as $suffix) {
            $suffix = strtolower(
                trim($suffix, '.'),
            );

            if (
                $hostname === $suffix
                || str_ends_with($hostname, '.' . $suffix)
            ) {
                return true;
            }
        }

        return false;
    }
}
