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

The result contains the claimed crawler and verification method:

```php
$result->verified;
$result->crawler;
$result->method;
```

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

## Identify without verifying

```php
$crawler = $verifier->identify(
    $_SERVER['HTTP_USER_AGENT'] ?? '',
);
```

This only identifies what the `User-Agent` claims to be.

## Verify a known crawler

```php
use JacyImp\CrawlerVerifier\Crawler;

$result = $verifier->verifyCrawler(
    Crawler::Googlebot,
    $_SERVER['REMOTE_ADDR'],
);
```

## Documentation

* [Caching](docs/caching.md)
* [Refreshing IP ranges](docs/refreshing-ip-ranges.md)
* [Local IP ranges](docs/local-ip-ranges.md)
* [Custom crawlers](docs/custom-crawlers.md)
* [How verification works](docs/verification.md)

## Requirements

* PHP 8.2+
* PSR-16 (`psr/simple-cache` ^3.0)
