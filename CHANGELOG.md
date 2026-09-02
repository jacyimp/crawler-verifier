# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

### Added

* Verify crawler identities using official IP ranges and forward-confirmed reverse DNS.
* Support for:

    * Googlebot
    * Bingbot
    * Applebot
    * DuckDuckBot
    * Pinterestbot
    * Baiduspider
    * GPTBot
    * OAI-SearchBot
    * OAI-AdsBot
    * PerplexityBot
    * Perplexity-User
* IPv4 and IPv6 CIDR matching.
* Bundled official IP range snapshots for IP-based crawlers.
* Explicit IP range refresh support using official upstream feeds.
* `refreshIfStale()` support for avoiding unnecessary upstream refreshes.
* PSR-16 caching for refreshed IP ranges and DNS lookups.
* Positive and negative DNS caching with configurable TTLs.
* Local IP range overrides.
* Configurable cache key prefix.
* Custom crawler identities and custom verification providers.
* Package-specific exception hierarchy.
* Verification results exposing the verified crawler and verification method.

### Changed

* Built-in crawler configuration is centralized in a crawler catalog.
* Built-in verification uses a shared data-driven provider instead of crawler-specific provider classes.
* IP range sources and update infrastructure are organized under dedicated `IpRange` namespaces.

### Security

* Native IP range fetching only permits HTTPS URLs.
* FCrDNS verification confirms both reverse hostname ownership and forward resolution back to the original IP.
* Invalid or empty upstream IP range data never replaces previously cached valid ranges.
* Runtime crawler verification never performs external HTTP requests.
