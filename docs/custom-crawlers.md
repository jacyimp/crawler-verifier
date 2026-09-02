# Custom Crawlers

Custom crawlers can be added without replacing the built-in crawlers.

## Define an identity

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

## Create a provider

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
        // Verify using your authoritative mechanism.

        return null;
    }
}
```

## Register it

```php
use JacyImp\CrawlerVerifier\CrawlerVerifier;

$verifier = new CrawlerVerifier(
    additionalProviders: [
        new MyCrawlerProvider(),
    ],
);
```

Or inject an existing provider:

```php
$verifier = new CrawlerVerifier(
    additionalProviders: [
        $myCrawlerProvider,
    ],
);
```

Built-in crawlers remain available.

## Dependency injection

Providers can be supplied directly by your container:

```php
$verifier = new CrawlerVerifier(
    cache: $cache,
    additionalProviders: $crawlerProviders,
);
```

`$crawlerProviders` may be any iterable of `CrawlerProvider` instances.
