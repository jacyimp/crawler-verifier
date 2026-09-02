<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Tests\Dns;

final class DnsResolverState
{
    public int $reverseCalls = 0;

    public int $forwardCalls = 0;
}
