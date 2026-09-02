<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Exception;

use RuntimeException;

final class IpRangeSourceException extends RuntimeException implements CrawlerVerifierException
{
    public static function invalidUrl(string $url): self
    {
        return new self(sprintf(
            'Invalid IP range URL "%s".',
            $url,
        ));
    }

    public static function unsupportedScheme(string $url): self
    {
        return new self(sprintf(
            'IP range URL must use HTTPS: "%s".',
            $url,
        ));
    }

    public static function unableToFetch(string $url): self
    {
        return new self(sprintf(
            'Unable to fetch IP ranges from "%s".',
            $url,
        ));
    }

    public static function unableToRead(string $path): self
    {
        return new self(sprintf(
            'Unable to read IP ranges from "%s".',
            $path,
        ));
    }
}
