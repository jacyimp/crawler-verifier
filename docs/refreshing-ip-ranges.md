# Refreshing IP Ranges

Crawler Verifier ships with bundled snapshots of official crawler IP ranges.

Refreshing them is optional and explicit.

Verification itself never downloads IP ranges.

## Refresh all ranges

```php
use JacyImp\CrawlerVerifier\IpRange\Update\IpRangeUpdater;

$updater = new IpRangeUpdater(
    cache: $cache,
);

$result = $updater->refresh();
```

## Refresh one crawler

```php
use JacyImp\CrawlerVerifier\Crawler;

$result = $updater->refresh(
    Crawler::GPTBot,
);
```

## Refresh only stale ranges

```php
$result = $updater->refreshIfStale(
    maxAgeSeconds: 86400,
);
```

Or for one crawler:

```php
$result = $updater->refreshIfStale(
    maxAgeSeconds: 86400,
    crawler: Crawler::Googlebot,
);
```

## Inspect the result

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

Failed downloads, invalid feeds, or empty feeds do not replace previously cached ranges.

## Keep verifier and updater aligned

If you use a custom cache prefix, use the same one for both:

```php
$verifier = new CrawlerVerifier(
    cache: $cache,
    cacheKeyPrefix: 'my_app.crawlers',
);

$updater = new IpRangeUpdater(
    cache: $cache,
    cacheKeyPrefix: 'my_app.crawlers',
);
```
