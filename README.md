# Magento 2 Full Page Cache Insight – Find Why Pages Miss the Cache (StorvixLabs_CacheInsight)

**StorvixLabs_CacheInsight** is a free Magento 2 extension that shows which storefront pages miss the
full-page cache (FPC) and why. It works with the built-in cache and with Varnish. Use it to find
`cacheable="false"` blocks, session cookies and private responses that keep pages out of the cache,
and to raise your FPC hit rate and page speed.

> Need help fixing a low cache hit rate, priority support or a custom feature?
> Email **[storvixlabs@gmail.com](mailto:storvixlabs@gmail.com)**.

## Why use Cache Insight?

Full-page cache hit rate is usually a black box: pages are slow, and nobody can say which pages miss or why.
Cache Insight logs every storefront request that is not answered from the cache, groups it by page type and
reports what keeps pages out of the cache.

- **Report:** System > Tools > **Cache Insight**
- **Configuration:** Stores > Configuration > Advanced > **Cache Insight** (logging is **off** by default)
- **CLI:** `bin/magento storvixlabs:cache-insight:report --days=7`, `bin/magento storvixlabs:cache-insight:reset`

## Features

- **Cache misses by page type** (`catalog_product_view`, `cms_index_index`, ...), each split into why it was not cached:
  *cacheable miss* (cold or purged cache), *block not cacheable*, *private response*, *session cookie set*,
  *redirect*, *error page*.
- **Find `cacheable="false"` blocks:** blocks marked `cacheable="false"` in the layout, with the page types they appear on.
  One such block makes the whole page uncacheable.
- **Cookie analysis:** cookies set by pages that missed and cookies sent with requests that missed, with the page types.
- **FPC hit rate** (built-in cache only), from sampled hit counting.
- **Command-line report** for quick checks on staging or production.
- **Privacy-friendly:** no URLs, IPs or cookie values are stored.

## How it works

An observer on the final response sees every request, cached or not. If a controller ran, the request was
a miss; the layout plugin notes `cacheable="false"` blocks; request and response cookie names are read from the
request and response. Counters are stored per day in two small tables (`storvixlabs_cacheinsight_daily`,
`storvixlabs_cacheinsight_cause`). Data older than the retention period is purged by cron.

## Varnish, performance and notes

- **Varnish:** PHP only sees misses, so the hit rate is not shown. Use the report to find *why* pages miss.
- **Performance cost:** one extra database write per missed page request, plus a sampled write for hits
  (default 5% of hits, scaled up). Turn logging off when you are done.
- Private pages (customer, checkout, ...) are ignored by default; edit **Ignored Pages** to change that.
- AJAX requests and non-GET requests are ignored.

## Installation

```bash
bin/magento module:enable StorvixLabs_CacheInsight
bin/magento setup:upgrade
bin/magento cache:flush
```

## FAQ

**Why is my Magento 2 full-page cache hit rate low?**
The most common causes are blocks with `cacheable="false"` in a layout file, cookies or sessions started on
public pages, and frequent cache purges. Cache Insight shows which of these affects each page type.

**How do I find `cacheable="false"` blocks in Magento 2?**
Enable logging, browse or crawl the store, then open System > Tools > Cache Insight. The report lists every
non-cacheable block and the page types it appears on.

**Does it work with Varnish?**
Yes. With Varnish, PHP only sees misses, so the report explains why pages miss but does not show a hit rate.

**Will it slow down my store?**
The overhead is one small database write per missed request and a sampled write for hits. Logging is off by
default and can be turned off once you have the data you need.

## Support & Contact

- **Bug reports and questions:** open a GitHub issue (free, best effort).
- **Cache and performance fixes, installation, priority support, custom features or Magento development:**
  [storvixlabs@gmail.com](mailto:storvixlabs@gmail.com)

## License

Copyright (c) 2026 StorvixLabs. Licensed, at your option, under the
[MIT License](LICENSE_MIT.txt) or the [Open Software License 3.0](LICENSE_OSL-3.0.txt).
