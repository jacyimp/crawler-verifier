<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\IpRange;

/**
 * @internal
 */
final class IpRangeMatcher
{
    /**
     * @param list<string> $ranges
     */
    public function matches(string $ip, array $ranges): bool
    {
        foreach ($ranges as $range) {
            if ($this->contains($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    public function contains(string $ip, string $range): bool
    {
        [$network, $prefixLength] = $this->parseRange($range);

        $ipBinary = inet_pton($ip);
        $networkBinary = inet_pton($network);

        if ($ipBinary === false || $networkBinary === false) {
            return false;
        }

        if (strlen($ipBinary) !== strlen($networkBinary)) {
            return false;
        }

        $bitLength = strlen($ipBinary) * 8;

        if ($prefixLength < 0 || $prefixLength > $bitLength) {
            return false;
        }

        $fullBytes = intdiv($prefixLength, 8);
        $remainingBits = $prefixLength % 8;

        if (
            $fullBytes > 0
            && substr($ipBinary, 0, $fullBytes) !== substr($networkBinary, 0, $fullBytes)
        ) {
            return false;
        }

        if ($remainingBits === 0) {
            return true;
        }

        $mask = (0xff << (8 - $remainingBits)) & 0xff;

        return (
                ord($ipBinary[$fullBytes]) & $mask
            ) === (
                ord($networkBinary[$fullBytes]) & $mask
            );
    }

    /**
     * @return array{string, int}
     */
    private function parseRange(string $range): array
    {
        $parts = explode('/', $range, 2);

        if (count($parts) !== 2 || !ctype_digit($parts[1])) {
            return ['', -1];
        }

        return [$parts[0], (int) $parts[1]];
    }
}
