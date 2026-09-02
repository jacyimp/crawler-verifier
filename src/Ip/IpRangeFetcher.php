<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Ip;

interface IpRangeFetcher
{
    public function fetch(string $url): string;
}
