<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Tests\IpRange;

use JacyImp\CrawlerVerifier\Exception\InvalidIpRangeDataException;
use JacyImp\CrawlerVerifier\IpRange\JsonIpRangeParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JsonIpRangeParser::class)]
#[UsesClass(InvalidIpRangeDataException::class)]
final class JsonIpRangeParserTest extends TestCase
{
    #[Test]
    public function itParsesIpv4AndIpv6Ranges(): void
    {
        $parser = new JsonIpRangeParser();

        $ranges = $parser->parse(<<<'JSON'
        {
            "prefixes": [
                {
                    "ipv4Prefix": "192.0.2.0/24"
                },
                {
                    "ipv6Prefix": "2001:db8::/32"
                }
            ]
        }
        JSON);

        self::assertSame([
            '192.0.2.0/24',
            '2001:db8::/32',
        ], $ranges);
    }

    #[Test]
    public function itAllowsAnEmptyPrefixList(): void
    {
        $parser = new JsonIpRangeParser();

        self::assertSame(
            [],
            $parser->parse('{"prefixes": []}'),
        );
    }

    #[Test]
    public function itRejectsInvalidJson(): void
    {
        $parser = new JsonIpRangeParser();

        $this->expectException(InvalidIpRangeDataException::class);

        $parser->parse('{');
    }

    #[Test]
    public function itRejectsDataWithoutPrefixes(): void
    {
        $parser = new JsonIpRangeParser();

        $this->expectException(InvalidIpRangeDataException::class);

        $parser->parse('{}');
    }

    #[Test]
    public function itRejectsAnInvalidIpRange(): void
    {
        $parser = new JsonIpRangeParser();

        $this->expectException(InvalidIpRangeDataException::class);

        $parser->parse(<<<'JSON'
        {
            "prefixes": [
                {
                    "ipv4Prefix": "definitely-not-a-range"
                }
            ]
        }
        JSON);
    }

    #[Test]
    public function itRejectsAnInvalidPrefixLength(): void
    {
        $parser = new JsonIpRangeParser();

        $this->expectException(InvalidIpRangeDataException::class);

        $parser->parse(<<<'JSON'
        {
            "prefixes": [
                {
                    "ipv4Prefix": "192.0.2.0/33"
                }
            ]
        }
        JSON);
    }
}
