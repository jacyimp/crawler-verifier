# Crawler Verifier

[![PHPStan level max](https://img.shields.io/badge/PHPStan-level%20max-brightgreen.svg)](https://phpstan.org/)
[![codecov](https://codecov.io/gh/jacyimp/crawler-verifier/branch/main/graph/badge.svg)](https://codecov.io/gh/jacyimp/crawler-verifier)

Verify that web crawlers are actually who they claim to be.

A `User-Agent` can be spoofed. Crawler Verifier checks the claimed crawler against official IP ranges or forward-confirmed reverse DNS.

```bash
composer require jacyimp/crawler-verifier
```

## Usage

```php
use JacyImp\CrawlerVerifier\CrawlerVerifier;

$verifier = CrawlerVerifier::create();

$result = $verifier->verify(
    userAgent: $_SERVER['HTTP_USER_AGENT'] ?? '',
    ip: $_SERVER['REMOTE_ADDR'],
);

if ($result->verified) {
    // Verified crawler.
}
```

The result tells you which crawler was claimed and how it was verified:

```php
$result->verified;
$result->crawler;
$result->method;
```

For example:

```php
use JacyImp\CrawlerVerifier\Crawler;
use JacyImp\CrawlerVerifier\VerificationMethod;

$result->crawler === Crawler::Googlebot;
$result->method === VerificationMethod::IpRange;
```

## Identify without verifying

```php
$crawler = $verifier->identify(
    $_SERVER['HTTP_USER_AGENT'] ?? '',
);
```

This only identifies the claimed crawler from the `User-Agent`.

It does **not** prove the request is genuine.

## Verify a known crawler

If you already know which crawler is being checked:

```php
use JacyImp\CrawlerVerifier\Crawler;

$result = $verifier->verifyCrawler(
    Crawler::Googlebot,
    $_SERVER['REMOTE_ADDR'],
);
```

## Supported crawlers

| Crawler         | Verification               |
| --------------- | -------------------------- |
| Googlebot       | IP ranges, FCrDNS fallback |
| Bingbot         | IP ranges, FCrDNS fallback |
| Applebot        | IP ranges, FCrDNS fallback |
| DuckDuckBot     | IP ranges                  |
| Pinterestbot    | FCrDNS                     |
| Baiduspider     | FCrDNS                     |
| GPTBot          | IP ranges                  |
| OAI-SearchBot   | IP ranges                  |
| OAI-AdsBot      | IP ranges                  |
| PerplexityBot   | IP ranges                  |
| Perplexity-User | IP ranges                  |

## IP ranges

The package includes bundled snapshots of official crawler IP ranges.

Runtime verification does not make external HTTP requests.

For IP-based crawlers, ranges are resolved in this order:

1. Explicit local range directories
2. Refreshed PSR-16 cache
3. Bundled snapshots

DNS-backed verification may perform DNS lookups.

## Cache

Caching is optional.

Crawler Verifier depends only on `psr/simple-cache`, so you can use any PSR-16 implementation.

```php
use JacyImp\CrawlerVerifier\CrawlerVerifier;
use JacyImp\CrawlerVerifier\CrawlerVerifierConfig;

$config = new CrawlerVerifierConfig(
    cache: $cache,
);

$verifier = CrawlerVerifier::create($config);
```

The cache is used for:

* refreshed IP ranges
* positive DNS results
* negative DNS results

Default DNS TTLs are:

```text
Positive: 3600 seconds
Negative: 300 seconds
```

They can be changed:

```php
$config = new CrawlerVerifierConfig(
    cache: $cache,
    dnsCacheTtlSeconds: 7200,
    dnsNegativeCacheTtlSeconds: 600,
);
```

A custom cache key prefix can also be supplied:

```php
$config = new CrawlerVerifierConfig(
    cache: $cache,
    cacheKeyPrefix: 'my_app.crawlers',
);
```

## Refresh IP ranges

Refreshing is explicit.

Crawler verification itself never downloads IP ranges.

```php
use JacyImp\CrawlerVerifier\IpRange\Update\IpRangeUpdater;

$updater = IpRangeUpdater::create($config);

$result = $updater->refresh();
```

Refresh a single crawler:

```php
use JacyImp\CrawlerVerifier\Crawler;

$result = $updater->refresh(
    Crawler::GPTBot,
);
```

Or only refresh stale data:

```php
$result = $updater->refreshIfStale(
    maxAgeSeconds: 86400,
);
```

A failed download or invalid feed does not replace the previous cached ranges.

Inspect the result:

```php
$result->successful();

$result->wasUpdated(
    Crawler::GPTBot,
);

$result->wasSkipped(
    Crawler::GPTBot,
);

$result->failed(
    Crawler::GPTBot,
);

$result->error(
    Crawler::GPTBot,
);
```

## Local IP ranges

You can provide your own range snapshots.

```php
$config = new CrawlerVerifierConfig(
    localRangeDirectories: [
        __DIR__ . '/crawler-ranges',
    ],
);
```

Files are named after the crawler:

```text
crawler-ranges/
├── googlebot.json
├── gptbot.json
└── perplexitybot.json
```

They use the same format as the official feeds:

```json
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
```

Explicit local ranges take priority over both refreshed and bundled data.

## Custom providers

You can bypass the default wiring completely:

```php
use JacyImp\CrawlerVerifier\CrawlerVerifier;

$verifier = new CrawlerVerifier([
    $provider,
]);
```

Custom providers implement:

```php
use JacyImp\CrawlerVerifier\CrawlerIdentity;
use JacyImp\CrawlerVerifier\Provider\CrawlerProvider;
use JacyImp\CrawlerVerifier\VerificationMethod;

enum MyCrawler: string implements CrawlerIdentity
{
    case Bot = 'my-crawler';

    public function id(): string
    {
        return $this->value;
    }
}

final class MyCrawlerProvider implements CrawlerProvider
{
    public function identify(string $userAgent): ?CrawlerIdentity
    {
        return str_contains($userAgent, 'MyCrawler/')
            ? MyCrawler::Bot
            : null;
    }

    public function supports(CrawlerIdentity $crawler): bool
    {
        return $crawler === MyCrawler::Bot;
    }

    public function verify(
        CrawlerIdentity $crawler,
        string $ip,
    ): ?VerificationMethod {
        // Verify the crawler using your own authoritative mechanism.
        return null;
    }
}
```

## Verification methods

### IP ranges

The request IP is checked against ranges published by the crawler operator.

Both IPv4 and IPv6 CIDRs are supported.

### Forward-confirmed reverse DNS

FCrDNS verification:

1. Reverse-resolves the request IP.
2. Validates the hostname against the crawler's official domain.
3. Resolves that hostname forward.
4. Confirms the original IP is among the returned addresses.

Checking only reverse DNS is not enough.

## Requirements

* PHP 8.2+
* `psr/simple-cache` ^3.0

No framework is required.
