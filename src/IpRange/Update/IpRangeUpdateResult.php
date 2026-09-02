<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\IpRange\Update;

use JacyImp\CrawlerVerifier\Crawler;

final readonly class IpRangeUpdateResult
{
    /**
     * @param list<Crawler> $updated
     * @param array<string, string> $errors
     * @param list<Crawler> $skipped
     */
    public function __construct(
        public array $updated,
        public array $errors,
        public array $skipped = [],
    ) {
    }

    public function successful(): bool
    {
        return $this->errors === [];
    }

    public function wasUpdated(Crawler $crawler): bool
    {
        return in_array(
            $crawler,
            $this->updated,
            true,
        );
    }

    public function wasSkipped(Crawler $crawler): bool
    {
        return in_array(
            $crawler,
            $this->skipped,
            true,
        );
    }

    public function failed(Crawler $crawler): bool
    {
        return isset(
            $this->errors[$crawler->value],
        );
    }

    public function error(Crawler $crawler): ?string
    {
        return $this->errors[$crawler->value]
            ?? null;
    }
}
