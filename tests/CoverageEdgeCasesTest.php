<?php
declare(strict_types=1);
namespace JacyImp\CrawlerVerifier\Tests;
use JacyImp\CrawlerVerifier\Catalog\BuiltInCrawlerCatalog;
use JacyImp\CrawlerVerifier\Crawler;
use JacyImp\CrawlerVerifier\CrawlerIdentity;
use JacyImp\CrawlerVerifier\CrawlerVerifier;
use JacyImp\CrawlerVerifier\Dns\CachingDnsResolver;
use JacyImp\CrawlerVerifier\Dns\DnsResolver;
use JacyImp\CrawlerVerifier\Exception\InvalidIpRangeDataException;
use JacyImp\CrawlerVerifier\Exception\IpRangeSourceException;
use JacyImp\CrawlerVerifier\IpRange\JsonIpRangeParser;
use JacyImp\CrawlerVerifier\IpRange\Source\DirectoryIpRangeSource;
use JacyImp\CrawlerVerifier\IpRange\Source\PsrCacheIpRangeSource;
use JacyImp\CrawlerVerifier\IpRange\Update\IpRangeFeed;
use JacyImp\CrawlerVerifier\IpRange\Update\IpRangeFetcher;
use JacyImp\CrawlerVerifier\IpRange\Update\IpRangeUpdater;
use JacyImp\CrawlerVerifier\Provider\BuiltInCrawlerProvider;
use JacyImp\CrawlerVerifier\Provider\CrawlerProvider;
use JacyImp\CrawlerVerifier\Tests\Support\ArrayCache;
use JacyImp\CrawlerVerifier\Tests\Support\FaultyCache;
use JacyImp\CrawlerVerifier\Tests\Support\NativeFunctions;
use JacyImp\CrawlerVerifier\VerificationMethod;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
final class CoverageEdgeCasesTest extends TestCase
{
    #[After]
    public function resetNativeFunctions(): void { NativeFunctions::reset(); }
    #[Test]
    public function itCoversIdentityAndCatalogMisses(): void
    {
        self::assertSame('googlebot', Crawler::Googlebot->id());
        self::assertNull((new BuiltInCrawlerCatalog([]))->find(Crawler::Googlebot));
    }
    #[Test]
    public function verifierSkipsUnsupportedProviders(): void
    {
        $identity = new class implements CrawlerIdentity { public function id(): string { return 'custom'; } };
        $provider = new class implements CrawlerProvider {
            public function identify(string $userAgent): ?CrawlerIdentity { return null; }
            public function supports(CrawlerIdentity $crawler): bool { return false; }
            public function verify(CrawlerIdentity $crawler, string $ip): ?VerificationMethod { return null; }
        };
        self::assertFalse((new CrawlerVerifier([$provider]))->verifyCrawler($identity, '192.0.2.1')->verified);
    }
    #[Test]
    public function cachingResolverToleratesFailuresAndMalformedValues(): void
    {
        $dns = new class implements DnsResolver {
            public function reverse(string $ip): ?string { return $ip === '' ? null : 'crawler.example'; }
            public function forward(string $hostname): array { return ['192.0.2.1']; }
        };
        $faulty = new CachingDnsResolver($dns, new FaultyCache(throwOnGet: true));
        self::assertSame('crawler.example', $faulty->reverse('not-an-ip'));
        self::assertSame(['192.0.2.1'], $faulty->forward('Crawler.Example.'));
        $cache = new ArrayCache();
        $resolver = new CachingDnsResolver($dns, $cache);
        $reverseKey = 'crawler_verifier.dns.reverse.' . hash('sha256', bin2hex((string) inet_pton('192.0.2.1')));
        $cache->set($reverseKey, 123);
        self::assertNull($resolver->reverse('192.0.2.1'));
        $forwardKey = 'crawler_verifier.dns.forward.' . hash('sha256', 'crawler.example');
        $cache->set($forwardKey, 'invalid');
        self::assertSame([], $resolver->forward('crawler.example'));
        $cache->set($forwardKey, ['192.0.2.1', 123]);
        self::assertSame(['192.0.2.1'], $resolver->forward('crawler.example'));
    }
    #[Test]
    public function parserRejectsAnInvalidIpWithANumericPrefix(): void
    {
        $this->expectException(InvalidIpRangeDataException::class);
        (new JsonIpRangeParser())->parse('{"prefixes":[{"ipv4Prefix":"invalid/24"}]}');
    }
    #[Test]
    public function directorySourceReportsReadFailures(): void
    {
        $directory = sys_get_temp_dir() . '/crawler-verifier-read-' . bin2hex(random_bytes(4));
        mkdir($directory);
        file_put_contents($directory . '/gptbot.json', '{}');
        NativeFunctions::$read = static fn (): false => false;
        try {
            $this->expectException(IpRangeSourceException::class);
            (new DirectoryIpRangeSource($directory))->rangesFor(Crawler::GPTBot);
        } finally {
            unlink($directory . '/gptbot.json');
            rmdir($directory);
        }
    }
    #[Test]
    public function cacheRangeSourceToleratesCacheFailures(): void
    {
        self::assertNull((new PsrCacheIpRangeSource(new FaultyCache(throwOnGet: true)))->entryFor(Crawler::GPTBot));
    }

    #[Test]
    public function updaterReportsUnsupportedCrawlersAndCacheFailures(): void
    {
        $unsupported = new IpRangeUpdater(new ArrayCache(), []);
        self::assertArrayHasKey(Crawler::PinterestBot->value, $unsupported->refreshIfStale(60, Crawler::PinterestBot)->errors);
        $fetcher = new class implements IpRangeFetcher {
            public function fetch(string $url): string { return '{"prefixes":[{"ipv4Prefix":"192.0.2.0/24"}]}'; }
        };
        $updater = new IpRangeUpdater(
            new FaultyCache(rejectSet: true),
            [new IpRangeFeed(Crawler::GPTBot, 'https://example.com/ranges.json')],
            $fetcher,
        );
        self::assertArrayHasKey(Crawler::GPTBot->value, $updater->refresh()->errors);
    }

    #[Test]
    public function providerReturnsNullWhenItsCatalogDoesNotContainTheCrawler(): void
    {
        $source = new class implements \JacyImp\CrawlerVerifier\IpRange\Source\IpRangeSource {
            public function rangesFor(Crawler $crawler): ?array { return null; }
        };
        self::assertNull((new BuiltInCrawlerProvider(new BuiltInCrawlerCatalog([]), $source))->verify(Crawler::Googlebot, '192.0.2.1'));
    }
}
