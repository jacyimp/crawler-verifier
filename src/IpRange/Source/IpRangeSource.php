<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\IpRange\Source;

use JacyImp\CrawlerVerifier\Crawler;

interface IpRangeSource
{
    /**
     * @return list<string>|null
     */
    public function rangesFor(Crawler $crawler): ?array;
}
