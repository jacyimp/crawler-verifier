<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Tests\IpRange\Update;

use JacyImp\CrawlerVerifier\Crawler;
use JacyImp\CrawlerVerifier\IpRange\Update\IpRangeUpdateResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class IpRangeUpdateResultTest extends TestCase
{
    #[Test]
    public function itReportsUpdatedCrawlers(): void
    {
        $result = new IpRangeUpdateResult(
            updated: [
                Crawler::GPTBot,
            ],
            errors: [],
        );

        self::assertTrue(
            $result->wasUpdated(
                Crawler::GPTBot,
            ),
        );
    }

    #[Test]
    public function itReportsSkippedCrawlers(): void
    {
        $result = new IpRangeUpdateResult(
            updated: [],
            errors: [],
            skipped: [
                Crawler::GPTBot,
            ],
        );

        self::assertTrue(
            $result->wasSkipped(
                Crawler::GPTBot,
            ),
        );
    }

    #[Test]
    public function itReportsFailures(): void
    {
        $result = new IpRangeUpdateResult(
            updated: [],
            errors: [
                Crawler::GPTBot->value => 'Failed.',
            ],
        );

        self::assertFalse(
            $result->successful(),
        );
        self::assertTrue(
            $result->failed(
                Crawler::GPTBot,
            ),
        );
        self::assertSame(
            'Failed.',
            $result->error(
                Crawler::GPTBot,
            ),
        );
    }
}
