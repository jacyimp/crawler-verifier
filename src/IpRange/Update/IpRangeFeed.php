<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\IpRange\Update;

use JacyImp\CrawlerVerifier\Crawler;

final readonly class IpRangeFeed
{
    public function __construct(
        public Crawler $crawler,
        public string $url,
    ) {
    }
}
