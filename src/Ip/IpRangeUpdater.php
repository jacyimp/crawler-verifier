<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Ip;

use JacyImp\CrawlerVerifier\Crawler;
use JacyImp\CrawlerVerifier\CrawlerVerifierConfig;
use JacyImp\CrawlerVerifier\Exception\InvalidConfigurationException;
use JacyImp\CrawlerVerifier\Exception\CrawlerVerifierException;
use JacyImp\CrawlerVerifier\Exception\InvalidIpRangeDataException;
use JacyImp\CrawlerVerifier\Exception\IpRangeUpdateException;
use Psr\SimpleCache\CacheException;
use Psr\SimpleCache\CacheInterface;

final class IpRangeUpdater
{
    /**
     * @var list<IpRangeFeed>
     */
    private array $feeds;

    /**
     * @param iterable<IpRangeFeed> $feeds
     */
    public function __construct(
        private readonly CacheInterface $cache,
        iterable $feeds,
        private readonly IpRangeFetcher $fetcher = new NativeIpRangeFetcher(),
        private readonly JsonIpRangeParser $parser = new JsonIpRangeParser(),
        private readonly string $cacheKeyPrefix = 'crawler_verifier',
    ) {
        $this->feeds = [...$feeds];
    }

    public static function create(
        CrawlerVerifierConfig $config,
    ): self {
        if ($config->cache === null) {
            throw InvalidConfigurationException::cacheRequiredForIpRangeRefresh();
        }

        return new self(
            cache: $config->cache,
            feeds: IpRangeFeedRegistry::defaults()->all(),
            cacheKeyPrefix: $config->cacheKeyPrefix,
        );
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

        if ($crawler !== null && $feeds === []) {
            return $this->unsupportedCrawlerResult(
                $crawler,
            );
        }

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
            } catch (CrawlerVerifierException|CacheException $exception) {
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
