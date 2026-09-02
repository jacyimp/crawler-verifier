<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Tests\IpRange\Source;

use JacyImp\CrawlerVerifier\Crawler;
use JacyImp\CrawlerVerifier\IpRange\Source\FallbackIpRangeSource;
use JacyImp\CrawlerVerifier\IpRange\Source\IpRangeSource;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(FallbackIpRangeSource::class)]
final class FallbackIpRangeSourceTest extends TestCase
{
    #[Test]
    public function itReturnsRangesFromTheFirstAvailableSource(): void
    {
        $source = new FallbackIpRangeSource([
            $this->source(null),
            $this->source(['192.0.2.0/24']),
            $this->source(['203.0.113.0/24']),
        ]);

        self::assertSame(
            ['192.0.2.0/24'],
            $source->rangesFor(Crawler::GPTBot),
        );
    }

    #[Test]
    public function itDoesNotFallBackWhenASourceProvidesAnEmptyRangeList(): void
    {
        $source = new FallbackIpRangeSource([
            $this->source([]),
            $this->source(['192.0.2.0/24']),
        ]);

        self::assertSame(
            [],
            $source->rangesFor(Crawler::GPTBot),
        );
    }

    #[Test]
    public function itReturnsNullWhenNoSourceHasRanges(): void
    {
        $source = new FallbackIpRangeSource([
            $this->source(null),
            $this->source(null),
        ]);

        self::assertNull(
            $source->rangesFor(Crawler::GPTBot),
        );
    }

    /**
     * @param list<string>|null $ranges
     */
    private function source(?array $ranges): IpRangeSource
    {
        return new class($ranges) implements IpRangeSource {
            /**
             * @param list<string>|null $ranges
             */
            public function __construct(
                private readonly ?array $ranges,
            ) {
            }

            public function rangesFor(Crawler $crawler): ?array
            {
                return $this->ranges;
            }
        };
    }
}
