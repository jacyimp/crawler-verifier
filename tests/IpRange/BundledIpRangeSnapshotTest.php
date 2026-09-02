<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Tests\IpRange;

use JacyImp\CrawlerVerifier\Catalog\BuiltInCrawlerCatalog;
use JacyImp\CrawlerVerifier\Catalog\CrawlerDefinition;
use JacyImp\CrawlerVerifier\IpRange\JsonIpRangeParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JsonIpRangeParser::class)]
#[UsesClass(BuiltInCrawlerCatalog::class)]
#[UsesClass(CrawlerDefinition::class)]
final class BundledIpRangeSnapshotTest extends TestCase
{
    #[Test]
    public function itHasAValidBundledSnapshotForEveryBuiltInIpRangeFeed(): void
    {
        $parser = new JsonIpRangeParser();

        foreach (BuiltInCrawlerCatalog::defaults()->withIpRangeFeed() as $definition) {
            $path = sprintf(
                '%s/resources/ip-ranges/%s.json',
                dirname(__DIR__, 2),
                $definition->crawler->value,
            );

            self::assertFileExists(
                $path,
                sprintf(
                    'Crawler "%s" has an IP range feed but no bundled snapshot.',
                    $definition->crawler->value,
                ),
            );

            $contents = file_get_contents($path);

            self::assertIsString(
                $contents,
                sprintf(
                    'Unable to read bundled IP range snapshot for "%s".',
                    $definition->crawler->value,
                ),
            );

            $ranges = $parser->parse($contents);

            self::assertNotEmpty(
                $ranges,
                sprintf(
                    'Bundled IP range snapshot for "%s" contains no ranges.',
                    $definition->crawler->value,
                ),
            );
        }
    }
}
