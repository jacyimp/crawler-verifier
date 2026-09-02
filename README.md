# Crawler Verifier

[![PHPStan level max](https://img.shields.io/badge/PHPStan-level%20max-brightgreen.svg)](https://phpstan.org/)
[![Coverage 100%](https://img.shields.io/badge/coverage-100%25-brightgreen.svg)](#)

Verify that web crawlers are actually who they claim to be.

```bash
composer require jacyimp/crawler-verifier
```

## Usage

```php
use JacyImp\CrawlerVerifier\CrawlerVerifier;

$verifier = new CrawlerVerifier();

$result = $verifier->verify(
    userAgent: $_SERVER['HTTP_USER_AGENT'] ?? '',
    ip: $_SERVER['REMOTE_ADDR'],
);

if ($result->verified) {
    // Genuine crawler.
}
```

Check who was verified and how:

```php
use JacyImp\CrawlerVerifier\Crawler;
use JacyImp\CrawlerVerifier\VerificationMethod;

$result->crawler === Crawler::Googlebot;
$result->method === VerificationMethod::IpRange;
```

## Supported crawlers

| Crawler         | Verification       |
| --------------- | ------------------ |
| Googlebot       | IP ranges + FCrDNS |
| Bingbot         | IP ranges + FCrDNS |
| Applebot        | IP ranges + FCrDNS |
| DuckDuckBot     | IP ranges          |
| Pinterestbot    | FCrDNS             |
| Baiduspider     | FCrDNS             |
| GPTBot          | IP ranges          |
| OAI-SearchBot   | IP ranges          |
| OAI-AdsBot      | IP ranges          |
| PerplexityBot   | IP ranges          |
| Perplexity-User | IP ranges          |

Official IP range snapshots are bundled with the package.

Runtime verification never downloads IP ranges. DNS-backed crawlers may perform DNS lookups.

## Cache

Pass any PSR-16 cache:

```php
$verifier = new CrawlerVerifier(
    cache: $cache,
);
```

The cache is used for:

* refreshed IP ranges
* positive DNS results
* negative DNS results

Optional tuning:

```php
$verifier = new CrawlerVerifier(
    cache: $cache,
    cacheKeyPrefix: 'my_app.crawlers',
    dnsCacheTtlSeconds: 7200,
    dnsNegativeCacheTtlSeconds: 600,
);
```

## Refresh IP ranges

Refreshing is explicit and requires a PSR-16 cache.

```php
use JacyImp\CrawlerVerifier\IpRange\Update\IpRangeUpdater;

$updater = new IpRangeUpdater(
    cache: $cache,
);

$result = $updater->refresh();
```

Refresh one crawler:

```php
use JacyImp\CrawlerVerifier\Crawler;

$updater->refresh(
    Crawler::GPTBot,
);
```

Only refresh stale ranges:

```php
$updater->refreshIfStale(
    maxAgeSeconds: 86400,
);
```

A failed or invalid refresh does not replace the previous cached ranges.

## Identify only

Need to know what the `User-Agent` claims to be without verifying it?

```php
$crawler = $verifier->identify(
    $_SERVER['HTTP_USER_AGENT'] ?? '',
);
```

This does **not** prove the crawler is genuine.

## Verify a known crawler

```php
use JacyImp\CrawlerVerifier\Crawler;

$result = $verifier->verifyCrawler(
    Crawler::Googlebot,
    $_SERVER['REMOTE_ADDR'],
);
```

## Local IP ranges

Local range files override cached and bundled ranges:

```php
$verifier = new CrawlerVerifier(
    localRangeDirectories: [
        __DIR__ . '/crawler-ranges',
    ],
);
```

```text
crawler-ranges/
├── googlebot.json
├── gptbot.json
└── perplexitybot.json
```

Format:

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

Range priority:

```text
local ranges
→ refreshed cache
→ bundled snapshots
```

## Custom crawlers

Add your own provider without replacing the built-ins:

```php
$verifier = new CrawlerVerifier(
    additionalProviders: [
        $myCrawlerProvider,
    ],
);
```

Define an identity:

```php
use JacyImp\CrawlerVerifier\CrawlerIdentity;

enum MyCrawler: string implements CrawlerIdentity
{
    case Bot = 'my-crawler';

    public function id(): string
    {
        return $this->value;
    }
}
```

And implement:

```php
use JacyImp\CrawlerVerifier\CrawlerIdentity;
use JacyImp\CrawlerVerifier\Provider\CrawlerProvider;
use JacyImp\CrawlerVerifier\VerificationMethod;

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
        // Verify using your authoritative source.

        return null;
    }
}
```

## How verification works

**IP ranges**

The request IP is matched against ranges published by the crawler operator. IPv4 and IPv6 CIDRs are supported.

**FCrDNS**

1. Reverse-resolve the request IP.
2. Verify the hostname belongs to the crawler.
3. Resolve the hostname forward.
4. Confirm the original IP is returned.

## Requirements

* PHP 8.2+
* PSR-16 (`psr/simple-cache` ^3.0)
