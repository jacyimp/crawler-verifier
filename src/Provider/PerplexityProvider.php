<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Provider;

use JacyImp\CrawlerVerifier\Crawler;

final class PerplexityProvider extends IpRangeCrawlerProvider
{
    protected function userAgentTokens(): array
    {
        return [
            'PerplexityBot' => Crawler::PerplexityBot,
            'Perplexity-User' => Crawler::PerplexityUser,
        ];
    }
}
