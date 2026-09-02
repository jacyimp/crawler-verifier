<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\IpRange\Update;

interface IpRangeFetcher
{
    /**
     * @throws \JacyImp\CrawlerVerifier\Exception\IpRangeSourceException
     */
    public function fetch(string $url): string;
}
