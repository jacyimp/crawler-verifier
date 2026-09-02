# Local IP Ranges

Local range files can override refreshed and bundled IP ranges.

```php
use JacyImp\CrawlerVerifier\CrawlerVerifier;

$verifier = new CrawlerVerifier(
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

## Format

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

Both IPv4 and IPv6 CIDRs are supported.

## Priority

IP ranges are resolved in this order:

```text
local ranges
→ refreshed cache
→ bundled snapshots
```

Multiple local directories can be supplied:

```php
$verifier = new CrawlerVerifier(
    localRangeDirectories: [
        '/etc/my-app/crawler-ranges',
        __DIR__ . '/crawler-ranges',
    ],
);
```

Earlier directories take priority.
