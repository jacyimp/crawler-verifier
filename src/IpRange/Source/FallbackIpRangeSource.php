<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\IpRange\Source;

use JacyImp\CrawlerVerifier\Crawler;

final class FallbackIpRangeSource implements IpRangeSource
{
    /**
     * @var list<IpRangeSource>
     */
    private array $sources;

    /**
     * @param iterable<IpRangeSource> $sources
     */
    public function __construct(iterable $sources)
    {
        $this->sources = array_values([...$sources]);
    }

    /**
     * @return list<string>|null
     */
    public function rangesFor(Crawler $crawler): ?array
    {
        foreach ($this->sources as $source) {
            $ranges = $source->rangesFor($crawler);

            if ($ranges !== null) {
                return $ranges;
            }
        }

        return null;
    }
}
