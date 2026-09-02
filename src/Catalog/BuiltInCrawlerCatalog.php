<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Catalog;

use JacyImp\CrawlerVerifier\Crawler;

final class BuiltInCrawlerCatalog
{
    /**
     * @var list<CrawlerDefinition>
     */
    private array $definitions;

    /**
     * @param iterable<CrawlerDefinition> $definitions
     */
    public function __construct(iterable $definitions)
    {
        $this->definitions = [...$definitions];
    }

    public static function defaults(): self
    {
        return new self([
            new CrawlerDefinition(
                crawler: Crawler::Googlebot,
                userAgentTokens: [
                    'Googlebot',
                    'Googlebot-Image',
                    'Googlebot-News',
                    'Googlebot-Video',
                ],
                ipRangeFeedUrl: 'https://developers.google.com/static/crawling/ipranges/common-crawlers.json',
                dnsSuffixes: [
                    'googlebot.com',
                ],
            ),
            new CrawlerDefinition(
                crawler: Crawler::Bingbot,
                userAgentTokens: [
                    'bingbot',
                    'msnbot',
                ],
                ipRangeFeedUrl: 'https://www.bing.com/toolbox/bingbot.json',
                dnsSuffixes: [
                    'search.msn.com',
                ],
            ),
            new CrawlerDefinition(
                crawler: Crawler::Applebot,
                userAgentTokens: [
                    'Applebot',
                ],
                ipRangeFeedUrl: 'https://search.developer.apple.com/applebot.json',
                dnsSuffixes: [
                    'applebot.apple.com',
                ],
            ),
            new CrawlerDefinition(
                crawler: Crawler::DuckDuckBot,
                userAgentTokens: [
                    'DuckDuckBot',
                ],
                ipRangeFeedUrl: 'https://duckduckgo.com/duckduckbot.json',
            ),
            new CrawlerDefinition(
                crawler: Crawler::PinterestBot,
                userAgentTokens: [
                    'Pinterestbot',
                    'Pinterest',
                ],
                dnsSuffixes: [
                    'pinterest.com',
                    'pinterestcrawler.com',
                ],
            ),
            new CrawlerDefinition(
                crawler: Crawler::BaiduSpider,
                userAgentTokens: [
                    'Baiduspider',
                    'Baiduspider-render',
                ],
                dnsSuffixes: [
                    'baidu.com',
                    'baidu.jp',
                ],
            ),
            new CrawlerDefinition(
                crawler: Crawler::GPTBot,
                userAgentTokens: [
                    'GPTBot',
                ],
                ipRangeFeedUrl: 'https://openai.com/gptbot.json',
            ),
            new CrawlerDefinition(
                crawler: Crawler::OaiSearchBot,
                userAgentTokens: [
                    'OAI-SearchBot',
                ],
                ipRangeFeedUrl: 'https://openai.com/searchbot.json',
            ),
            new CrawlerDefinition(
                crawler: Crawler::OaiAdsBot,
                userAgentTokens: [
                    'OAI-AdsBot',
                ],
                ipRangeFeedUrl: 'https://openai.com/adsbot.json',
            ),
            new CrawlerDefinition(
                crawler: Crawler::PerplexityBot,
                userAgentTokens: [
                    'PerplexityBot',
                ],
                ipRangeFeedUrl: 'https://www.perplexity.com/perplexitybot.json',
            ),
            new CrawlerDefinition(
                crawler: Crawler::PerplexityUser,
                userAgentTokens: [
                    'Perplexity-User',
                ],
                ipRangeFeedUrl: 'https://www.perplexity.com/perplexity-user.json',
            ),
        ]);
    }

    /**
     * @return list<CrawlerDefinition>
     */
    public function all(): array
    {
        return $this->definitions;
    }

    public function find(Crawler $crawler): ?CrawlerDefinition
    {
        foreach ($this->definitions as $definition) {
            if ($definition->crawler === $crawler) {
                return $definition;
            }
        }

        return null;
    }

    /**
     * @return list<CrawlerDefinition>
     */
    public function withIpRangeFeed(): array
    {
        return array_values(array_filter(
            $this->definitions,
            static fn (CrawlerDefinition $definition): bool => $definition->hasIpRangeFeed(),
        ));
    }
}
