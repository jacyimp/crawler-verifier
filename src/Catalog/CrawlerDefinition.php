<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Catalog;

use JacyImp\CrawlerVerifier\Crawler;

final readonly class CrawlerDefinition
{
    /**
     * @param non-empty-list<string> $userAgentTokens
     * @param list<string> $dnsSuffixes
     */
    public function __construct(
        public Crawler $crawler,
        public array $userAgentTokens,
        public ?string $ipRangeFeedUrl = null,
        public array $dnsSuffixes = [],
    ) {
    }

    public function hasIpRangeFeed(): bool
    {
        return $this->ipRangeFeedUrl !== null;
    }

    public function hasDnsVerification(): bool
    {
        return $this->dnsSuffixes !== [];
    }
}
