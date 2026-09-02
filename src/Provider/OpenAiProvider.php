<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Provider;

use JacyImp\CrawlerVerifier\Crawler;

final class OpenAiProvider extends IpRangeCrawlerProvider
{
    protected function userAgentTokens(): array
    {
        return [
            'GPTBot' => Crawler::GPTBot,
            'OAI-SearchBot' => Crawler::OaiSearchBot,
            'OAI-AdsBot' => Crawler::OaiAdsBot,
        ];
    }
}
