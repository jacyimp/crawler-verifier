<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\IpRange\Update;

use JacyImp\CrawlerVerifier\Catalog\BuiltInCrawlerCatalog;
use JacyImp\CrawlerVerifier\Crawler;
use JacyImp\CrawlerVerifier\Exception\CrawlerVerifierException;
use JacyImp\CrawlerVerifier\Exception\InvalidConfigurationException;
use JacyImp\CrawlerVerifier\Exception\InvalidIpRangeDataException;
use JacyImp\CrawlerVerifier\Exception\IpRangeUpdateException;
use JacyImp\CrawlerVerifier\IpRange\JsonIpRangeParser;
use JacyImp\CrawlerVerifier\IpRange\Source\PsrCacheIpRangeSource;
use Psr\SimpleCache\CacheException;
use Psr\SimpleCache\CacheInterface;

final class IpRangeUpdater
{
    /**
     * @var list<IpRangeFeed>
     */
    private array $feeds;

    /**
     * @param iterable<IpRangeFeed>|null $feeds
     */
    public function __construct(
        private readonly CacheInterface $cache,
        ?iterable $feeds = null,
        ?IpRangeFetcher $fetcher = null,
        ?JsonIpRangeParser $parser = null,
        private readonly string $cacheKeyPrefix = 'crawler_verifier',
    ) {
        if (
            $this->cacheKeyPrefix === ''
            || preg_match('/^[A-Za-z0-9_.]+$/', $this->cacheKeyPrefix) !== 1
        ) {
            throw InvalidConfigurationException::invalidCacheKeyPrefix();
        }

        $this->feeds = array_values([...($feeds ?? self::defaultFeeds())]);
        $this->fetcher = $fetcher ?? new NativeIpRangeFetcher();
        $this->parser = $parser ?? new JsonIpRangeParser();
    }

    private readonly IpRangeFetcher $fetcher;

    private readonly JsonIpRangeParser $parser;

    /**
     * @return list<IpRangeFeed>
     */
    private static function defaultFeeds(): array
    {
        $feeds = [];

        foreach (BuiltInCrawlerCatalog::defaults()->withIpRangeFeed() as $definition) {
            $feeds[] = new IpRangeFeed(
                crawler: $definition->crawler,
                url: sprintf('%s', $definition->ipRangeFeedUrl),
            );
        }

        return $feeds;
    }

    public function refresh(
        ?Crawler $crawler = null,
    ): IpRangeUpdateResult {
        return $this->refreshFeeds(
            feeds: $this->resolveFeeds($crawler),
            crawler: $crawler,
        );
    }

    public function refreshIfStale(
        int $maxAgeSeconds,
        ?Crawler $crawler = null,
    ): IpRangeUpdateResult {
        if ($maxAgeSeconds < 0) {
            throw InvalidConfigurationException::negativeMaximumIpRangeAge();
        }

        $feeds = $this->resolveFeeds(
            $crawler,
        );

        $stale = [];
        $skipped = [];

        foreach ($feeds as $feed) {
            if (
                $this->isFresh(
                    $feed->crawler,
                    $maxAgeSeconds,
                )
            ) {
                $skipped[] = $feed->crawler;

                continue;
            }

            $stale[] = $feed;
        }

        $result = $this->refreshFeeds(
            feeds: $stale,
            crawler: $crawler,
        );

        return new IpRangeUpdateResult(
            updated: $result->updated,
            errors: $result->errors,
            skipped: $skipped,
        );
    }

    /**
     * @param list<IpRangeFeed> $feeds
     */
    private function refreshFeeds(
        array $feeds,
        ?Crawler $crawler = null,
    ): IpRangeUpdateResult {
        if ($crawler !== null && $feeds === []) {
            return $this->unsupportedCrawlerResult(
                $crawler,
            );
        }

        $updated = [];
        $errors = [];

        foreach ($feeds as $feed) {
            try {
                $this->refreshFeed($feed);

                $updated[] = $feed->crawler;
            } catch (CrawlerVerifierException | CacheException $exception) {
                $errors[$feed->crawler->value] = $exception->getMessage();
            }
        }

        return new IpRangeUpdateResult(
            updated: $updated,
            errors: $errors,
        );
    }

    private function refreshFeed(
        IpRangeFeed $feed,
    ): void {
        $contents = $this->fetcher->fetch(
            $feed->url,
        );

        $ranges = $this->parser->parse(
            $contents,
        );

        if ($ranges === []) {
            throw InvalidIpRangeDataException::emptyFeed(
                $feed->crawler,
            );
        }

        if (
            !$this->cache->set(
                PsrCacheIpRangeSource::key(
                    $feed->crawler,
                    $this->cacheKeyPrefix,
                ),
                [
                    'ranges' => $ranges,
                    'refreshed_at' => time(),
                ],
            )
        ) {
            throw IpRangeUpdateException::unableToCache(
                $feed->crawler,
            );
        }
    }

    private function isFresh(
        Crawler $crawler,
        int $maxAgeSeconds,
    ): bool {
        $source = new PsrCacheIpRangeSource(
            cache: $this->cache,
            cacheKeyPrefix: $this->cacheKeyPrefix,
        );

        $entry = $source->entryFor(
            $crawler,
        );

        if ($entry === null) {
            return false;
        }

        return $entry['refreshed_at']
            > time() - $maxAgeSeconds;
    }

    /**
     * @return list<IpRangeFeed>
     */
    private function resolveFeeds(
        ?Crawler $crawler,
    ): array {
        if ($crawler === null) {
            return $this->feeds;
        }

        return array_values(array_filter(
            $this->feeds,
            static fn (IpRangeFeed $feed): bool => $feed->crawler === $crawler,
        ));
    }

    private function unsupportedCrawlerResult(
        Crawler $crawler,
    ): IpRangeUpdateResult {
        return new IpRangeUpdateResult(
            updated: [],
            errors: [
                $crawler->value => sprintf(
                    'No IP range feed is configured for "%s".',
                    $crawler->value,
                ),
            ],
        );
    }
}
