<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Ip;

use InvalidArgumentException;
use JacyImp\CrawlerVerifier\Crawler;

final class IpRangeFeedRegistry
{
    /**
     * @var list<IpRangeFeed>
     */
    private array $feeds;

    /**
     * @param iterable<IpRangeFeed> $feeds
     */
    public function __construct(iterable $feeds)
    {
        $this->feeds = [];

        foreach ($feeds as $feed) {
            if ($this->has($feed->crawler)) {
                throw new InvalidArgumentException(sprintf(
                    'An IP range feed is already registered for "%s".',
                    $feed->crawler->value,
                ));
            }

            $this->feeds[] = $feed;
        }
    }

    public static function defaults(): self
    {
        return new self([
            new IpRangeFeed(
                Crawler::GPTBot,
                'https://openai.com/gptbot.json',
            ),
            new IpRangeFeed(
                Crawler::OaiSearchBot,
                'https://openai.com/searchbot.json',
            ),
            new IpRangeFeed(
                Crawler::OaiAdsBot,
                'https://openai.com/adsbot.json',
            ),
            new IpRangeFeed(
                Crawler::Googlebot,
                'https://developers.google.com/static/crawling/ipranges/common-crawlers.json',
            ),
            new IpRangeFeed(
                Crawler::Bingbot,
                'https://www.bing.com/toolbox/bingbot.json',
            ),
            new IpRangeFeed(
                Crawler::Applebot,
                'https://search.developer.apple.com/applebot.json',
            ),
            new IpRangeFeed(
                Crawler::DuckDuckBot,
                'https://duckduckgo.com/duckduckbot.json',
            ),
            new IpRangeFeed(
                Crawler::PerplexityBot,
                'https://www.perplexity.com/perplexitybot.json',
            ),
            new IpRangeFeed(
                Crawler::PerplexityUser,
                'https://www.perplexity.com/perplexity-user.json',
            ),
        ]);
    }

    /**
     * @return list<IpRangeFeed>
     */
    public function all(): array
    {
        return $this->feeds;
    }

    public function find(Crawler $crawler): ?IpRangeFeed
    {
        foreach ($this->feeds as $feed) {
            if ($feed->crawler === $crawler) {
                return $feed;
            }
        }

        return null;
    }

    public function has(Crawler $crawler): bool
    {
        return $this->find($crawler) !== null;
    }
}
