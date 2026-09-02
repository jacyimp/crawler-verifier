<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier\Tests\Dns;

use JacyImp\CrawlerVerifier\Dns\CachingDnsResolver;
use JacyImp\CrawlerVerifier\Dns\DnsResolver;
use JacyImp\CrawlerVerifier\Exception\InvalidConfigurationException;
use JacyImp\CrawlerVerifier\Tests\Support\ArrayCache;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[UsesClass(InvalidConfigurationException::class)]
final class CachingDnsResolverTest extends TestCase
{
    #[Test]
    public function itCachesAReverseDnsResult(): void
    {
        $state = $this->state();

        $resolver = new CachingDnsResolver(
            resolver: $this->resolver(
                state: $state,
                reverse: 'crawl.googlebot.com',
            ),
            cache: new ArrayCache(),
        );

        self::assertSame(
            'crawl.googlebot.com',
            $resolver->reverse('192.0.2.1'),
        );

        self::assertSame(
            'crawl.googlebot.com',
            $resolver->reverse('192.0.2.1'),
        );

        self::assertSame(
            1,
            $state->reverseCalls,
        );
    }

    #[Test]
    public function itCachesANegativeReverseDnsResult(): void
    {
        $state = $this->state();

        $resolver = new CachingDnsResolver(
            resolver: $this->resolver(
                state: $state,
                reverse: null,
            ),
            cache: new ArrayCache(),
        );

        self::assertNull(
            $resolver->reverse('192.0.2.1'),
        );

        self::assertNull(
            $resolver->reverse('192.0.2.1'),
        );

        self::assertSame(
            1,
            $state->reverseCalls,
        );
    }

    #[Test]
    public function itCachesForwardDnsResults(): void
    {
        $state = $this->state();

        $resolver = new CachingDnsResolver(
            resolver: $this->resolver(
                state: $state,
                forward: [
                    '192.0.2.1',
                ],
            ),
            cache: new ArrayCache(),
        );

        $resolver->forward(
            'crawl.googlebot.com',
        );

        $resolver->forward(
            'crawl.googlebot.com',
        );

        self::assertSame(
            1,
            $state->forwardCalls,
        );
    }

    #[Test]
    public function itCachesANegativeForwardDnsResult(): void
    {
        $state = $this->state();

        $resolver = new CachingDnsResolver(
            resolver: $this->resolver(
                state: $state,
                forward: [],
            ),
            cache: new ArrayCache(),
        );

        $resolver->forward(
            'crawl.googlebot.com',
        );

        $resolver->forward(
            'crawl.googlebot.com',
        );

        self::assertSame(
            1,
            $state->forwardCalls,
        );
    }

    #[Test]
    public function itExpiresPositiveResults(): void
    {
        $state = $this->state();

        $resolver = new CachingDnsResolver(
            resolver: $this->resolver(
                state: $state,
                reverse: 'crawl.googlebot.com',
            ),
            cache: new ArrayCache(),
            positiveTtlSeconds: 0,
        );

        $resolver->reverse(
            '192.0.2.1',
        );

        $resolver->reverse(
            '192.0.2.1',
        );

        self::assertSame(
            2,
            $state->reverseCalls,
        );
    }

    #[Test]
    public function itExpiresNegativeResultsSeparately(): void
    {
        $state = $this->state();

        $resolver = new CachingDnsResolver(
            resolver: $this->resolver(
                state: $state,
                reverse: null,
            ),
            cache: new ArrayCache(),
            positiveTtlSeconds: 3600,
            negativeTtlSeconds: 0,
        );

        $resolver->reverse(
            '192.0.2.1',
        );

        $resolver->reverse(
            '192.0.2.1',
        );

        self::assertSame(
            2,
            $state->reverseCalls,
        );
    }

    #[Test]
    public function itRejectsANegativePositiveTtlWithAPackageException(): void
    {
        $this->expectException(
            InvalidConfigurationException::class,
        );

        new CachingDnsResolver(
            resolver: $this->resolver(
                state: $this->state(),
            ),
            cache: new ArrayCache(),
            positiveTtlSeconds: -1,
        );
    }

    #[Test]
    public function itRejectsANegativeNegativeTtlWithAPackageException(): void
    {
        $this->expectException(
            InvalidConfigurationException::class,
        );

        new CachingDnsResolver(
            resolver: $this->resolver(
                state: $this->state(),
            ),
            cache: new ArrayCache(),
            negativeTtlSeconds: -1,
        );
    }

    #[Test]
    public function itNormalizesEquivalentIpv6Addresses(): void
    {
        $state = $this->state();

        $resolver = new CachingDnsResolver(
            resolver: $this->resolver(
                state: $state,
                reverse: 'crawl.googlebot.com',
            ),
            cache: new ArrayCache(),
        );

        $resolver->reverse(
            '2001:db8::1',
        );

        $resolver->reverse(
            '2001:0db8:0000:0000:0000:0000:0000:0001',
        );

        self::assertSame(
            1,
            $state->reverseCalls,
        );
    }

    #[Test]
    public function itNormalizesHostnamesForForwardLookups(): void
    {
        $state = $this->state();

        $resolver = new CachingDnsResolver(
            resolver: $this->resolver(
                state: $state,
                forward: [
                    '192.0.2.1',
                ],
            ),
            cache: new ArrayCache(),
        );

        $resolver->forward(
            'Crawl.Googlebot.COM.',
        );

        $resolver->forward(
            'crawl.googlebot.com',
        );

        self::assertSame(
            1,
            $state->forwardCalls,
        );
    }

    private function state(): DnsResolverState
    {
        return new DnsResolverState();
    }

    /**
     * @param list<string> $forward
     */
    private function resolver(
        DnsResolverState $state,
        ?string $reverse = null,
        array $forward = [],
    ): DnsResolver {
        return new class (
            $state,
            $reverse,
            $forward,
        ) implements DnsResolver {
            /**
             * @param list<string> $forward
             */
            public function __construct(
                private readonly DnsResolverState $state,
                private readonly ?string $reverse,
                private readonly array $forward,
            ) {
            }

            public function reverse(
                string $ip,
            ): ?string {
                ++$this->state->reverseCalls;

                return $this->reverse;
            }

            /**
             * @return list<string>
             */
            public function forward(
                string $hostname,
            ): array {
                ++$this->state->forwardCalls;

                return $this->forward;
            }
        };
    }
}
