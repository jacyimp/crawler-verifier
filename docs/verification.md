# How Verification Works

A crawler `User-Agent` is only a claim.

For example:

```text
Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)
```

Anyone can send that header.

Crawler Verifier first identifies the claimed crawler, then verifies the request IP using an authoritative mechanism.

## IP ranges

For crawlers that publish official IP ranges:

```text
request IP
    ↓
official CIDR ranges
    ↓
match?
```

Both IPv4 and IPv6 are supported.

Example:

```text
66.249.66.1
    ↓
66.249.64.0/19
    ↓
verified
```

Bundled official snapshots are used by default.

If configured, the priority is:

```text
local ranges
→ refreshed cache
→ bundled snapshots
```

## Forward-confirmed reverse DNS

Some crawler operators verify their crawlers through DNS.

FCrDNS performs both reverse and forward resolution:

```text
request IP
    ↓
reverse DNS
    ↓
crawler hostname
    ↓
forward DNS
    ↓
original IP present?
```

The hostname must also match the crawler's official domain.

For example:

```text
66.249.66.1
    ↓
crawl-66-249-66-1.googlebot.com
    ↓
googlebot.com ✓
    ↓
66.249.66.1
    ↓
verified
```

A reverse DNS lookup alone is not sufficient.

This prevents accepting hostnames such as:

```text
googlebot.com.example.org
fakegooglebot.com
```

## Verification result

Successful verification returns:

```php
$result->verified === true;
$result->crawler;
$result->method;
```

Failed verification keeps the claimed identity when one was recognized:

```php
$result->verified === false;
$result->crawler;
$result->method === null;
```

An unknown `User-Agent` returns:

```php
$result->verified === false;
$result->crawler === null;
$result->method === null;
```

## No runtime HTTP requests

Normal crawler verification never downloads external IP feeds.

IP feed downloads only happen when you explicitly use `IpRangeUpdater`.

DNS-backed verification may perform DNS lookups.
