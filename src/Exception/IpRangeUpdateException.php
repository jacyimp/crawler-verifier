<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Exception;

use JacyImp\CrawlerVerifier\Crawler;
use RuntimeException;

final class IpRangeUpdateException extends RuntimeException implements CrawlerVerifierException
{
    public static function unableToCache(Crawler $crawler): self
    {
        return new self(sprintf(
            'Unable to cache IP ranges for "%s".',
            $crawler->value,
        ));
    }
}
