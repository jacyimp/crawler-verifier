<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Provider;

use JacyImp\CrawlerVerifier\Crawler;

final class DuckDuckGoProvider extends IpRangeCrawlerProvider
{
    protected function userAgentTokens(): array
    {
        return [
            'DuckDuckBot' => Crawler::DuckDuckBot,
        ];
    }
}
