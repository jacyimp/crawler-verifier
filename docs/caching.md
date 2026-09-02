# Caching

Crawler Verifier accepts any PSR-16 cache.

```php
use JacyImp\CrawlerVerifier\CrawlerVerifier;

$verifier = new CrawlerVerifier(
    cache: $cache,
);
```

The cache is used for:

* refreshed IP ranges
* positive DNS results
* negative DNS results

## DNS TTLs

Defaults:

```text
Positive: 3600 seconds
Negative: 300 seconds
```

Override them when needed:

```php
$verifier = new CrawlerVerifier(
    cache: $cache,
    dnsCacheTtlSeconds: 7200,
    dnsNegativeCacheTtlSeconds: 600,
);
```

## Cache prefix

The default prefix is:

```text
crawler_verifier
```

Override it:

```php
$verifier = new CrawlerVerifier(
    cache: $cache,
    cacheKeyPrefix: 'my_app.crawlers',
);
```

Use the same prefix when refreshing IP ranges:

```php
use JacyImp\CrawlerVerifier\IpRange\Update\IpRangeUpdater;

$updater = new IpRangeUpdater(
    cache: $cache,
    cacheKeyPrefix: 'my_app.crawlers',
);
```
