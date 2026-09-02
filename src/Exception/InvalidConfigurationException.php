<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Exception;

use InvalidArgumentException;
use JacyImp\CrawlerVerifier\Crawler;

final class InvalidConfigurationException extends InvalidArgumentException implements CrawlerVerifierException
{
    public static function invalidCacheKeyPrefix(): self
    {
        return new self(
            'Cache key prefix may contain only letters, numbers, underscores and dots.',
        );
    }

    public static function negativeDnsCacheTtl(): self
    {
        return new self(
            'DNS cache TTL cannot be negative.',
        );
    }

    public static function negativeDnsNegativeCacheTtl(): self
    {
        return new self(
            'Negative DNS cache TTL cannot be negative.',
        );
    }

    public static function negativePositiveDnsCacheTtl(): self
    {
        return new self(
            'Positive DNS cache TTL cannot be negative.',
        );
    }

    public static function cacheRequiredForIpRangeRefresh(): self
    {
        return new self(
            'A PSR-16 cache is required to refresh IP ranges.',
        );
    }

    public static function negativeMaximumIpRangeAge(): self
    {
        return new self(
            'Maximum IP range age cannot be negative.',
        );
    }

    public static function duplicateIpRangeFeed(Crawler $crawler): self
    {
        return new self(sprintf(
            'An IP range feed is already registered for "%s".',
            $crawler->value,
        ));
    }
}
