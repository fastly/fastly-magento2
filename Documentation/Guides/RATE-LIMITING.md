# Rate Limiting

Please note Rate Limiting is available in version 1.2.93+ of the module. 

This guide will show how to enable rate limiting. Rate limiting allows you to restrict how many requests a specific IP address
can make in a period of time e.g. 10 requests in one hour. It's intended to guard against abuse of sensitive or computationally
expensive endpoints e.g. /paypal/transparent/requestSecureToken etc.

This feature is provided with following limitations

1. You can only rate-limit specific paths. Paths are defined within the UI using regular expressions. 
2. Any paths defined as rate-limited WILL NOT be cached by Fastly. Therefore it's not a good idea to use to rate limit product, catalog 
   or other cacheable pages.
3. Rate limit applies to requests per IP address against the URL paths that have been specified. No other paths are rate limited


To enable Rate Limiting, go to:
```
Magento admin > Stores > Configuration > Advanced > System > Full Page Cache > Fastly Configuration
```
Under *Rate Limiting* tab, you can enable by clicking **Enable/Disable** button

![Rate Limiting Main Screen](../images/guides/rate-limiting/ratelimiting1.png "Rate Limiting Main Screen")

After you have enabled the feature you will need to click on Manage Paths button

![Rate Limiting Manage Paths](../images/guides/rate-limiting/ratelimiting2.png "Rate Limiting Manage Paths")

