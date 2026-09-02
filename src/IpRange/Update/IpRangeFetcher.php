<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\IpRange\Update;

use JacyImp\CrawlerVerifier\Exception\IpRangeSourceException;

interface IpRangeFetcher
{
    /**
     * @throws IpRangeSourceException
     */
    public function fetch(string $url): string;
}
