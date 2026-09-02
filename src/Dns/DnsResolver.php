<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Dns;

interface DnsResolver
{
    public function reverse(string $ip): ?string;

    /**
     * @return list<string>
     */
    public function forward(string $hostname): array;
}
