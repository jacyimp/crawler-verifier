<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Tests;

use JacyImp\CrawlerVerifier\Crawler;
use JacyImp\CrawlerVerifier\VerificationMethod;
use JacyImp\CrawlerVerifier\VerificationResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(VerificationResult::class)]
final class VerificationResultTest extends TestCase
{
    #[Test]
    public function itCreatesAVerifiedResult(): void
    {
        $result = VerificationResult::verified(
            Crawler::GPTBot,
            VerificationMethod::IpRange,
        );

        self::assertTrue($result->verified);
        self::assertSame(Crawler::GPTBot, $result->crawler);
        self::assertSame(VerificationMethod::IpRange, $result->method);
    }

    #[Test]
    public function itCreatesAnUnverifiedResultForAKnownCrawler(): void
    {
        $result = VerificationResult::unverified(Crawler::Googlebot);

        self::assertFalse($result->verified);
        self::assertSame(Crawler::Googlebot, $result->crawler);
        self::assertNull($result->method);
    }

    #[Test]
    public function itCreatesAnUnverifiedResultForAnUnknownCrawler(): void
    {
        $result = VerificationResult::unverified();

        self::assertFalse($result->verified);
        self::assertNull($result->crawler);
        self::assertNull($result->method);
    }
}
