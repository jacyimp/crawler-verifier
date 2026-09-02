<?php

declare(strict_types=1);

namespace JacyImp\CrawlerVerifier;

enum Crawler: string
{
    case Googlebot = 'googlebot';
    case Bingbot = 'bingbot';
    case Applebot = 'applebot';
    case DuckDuckBot = 'duckduckbot';
    case PinterestBot = 'pinterestbot';
    case BaiduSpider = 'baiduspider';

    case GPTBot = 'gptbot';
    case OaiSearchBot = 'oai-searchbot';
    case OaiAdsBot = 'oai-adsbot';

    case PerplexityBot = 'perplexitybot';
    case PerplexityUser = 'perplexity-user';
}
