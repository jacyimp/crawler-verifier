<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Exception;

use JacyImp\CrawlerVerifier\Crawler;
use JsonException;
use RuntimeException;

final class InvalidIpRangeDataException extends RuntimeException implements CrawlerVerifierException
{
    public static function unableToParse(JsonException $previous): self
    {
        return new self(
            'Unable to parse IP range data.',
            previous: $previous,
        );
    }

    public static function invalidData(): self
    {
        return new self(
            'Invalid IP range data.',
        );
    }

    public static function invalidPrefix(): self
    {
        return new self(
            'Invalid IP range prefix.',
        );
    }

    public static function emptyFeed(Crawler $crawler): self
    {
        return new self(sprintf(
            'IP range feed for "%s" contains no ranges.',
            $crawler->value,
        ));
    }
}
