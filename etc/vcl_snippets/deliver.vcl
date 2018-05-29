    if (resp.status >= 500 && resp.status < 600) {
        /* restart if the stale object is available */
        if (stale.exists) {
            restart;
        }
    }

    # Send no cache headers to end users for non-static content. Also make sure
    # we only set this on the edge nodes and not on shields
    if (req.url !~ "^/(pub/)?(media|static)/.*" && !req.http.Fastly-FF ) {
        set resp.http.Cache-Control = "no-store, no-cache, must-revalidate, max-age=0";
    }

    # Execute only on the edge nodes
    if ( !req.http.Fastly-FF ) {
        # Remove X-Magento-Vary and HTTPs Vary served to the user
        set resp.http.Vary = regsub(resp.http.Vary, "X-Magento-Vary,Https", "Cookie");
        remove resp.http.X-Magento-Tags;

        # Set headers based on suggestions in https://www.fastly.com/blog/headers-we-want
        set resp.http.Content-Security-Policy = "default-src 'self'; frame-ancestors 'self'";
        set resp.http.Referer-Policy = "origin-when-cross-origin";
        set resp.http.X-XSS-Protection = "1; mode=block";
        set resp.http.X-Content-Type-Options = "nosniff";

        # CORS policy. Consider checking this against an allowlist
        if (req.http.Origin) {
          set resp.http.Access-Control-Allow-Origin = req.http.Origin;
          set resp.http.Access-Control-Allow-Methods = "GET,HEAD,POST,OPTIONS";
        }

        # Remove headers based on post in https://www.fastly.com/blog/headers-we-dont-want
        remove resp.http.Expires;
        remove resp.http.Pragma;
        remove resp.http.X-Frame-Options;
    }

    # Add an easy way to see whether custom Fastly VCL has been uploaded
    if ( req.http.Fastly-Debug ) {
        set resp.http.Fastly-Magento-VCL-Uploaded = "1.2.54";
    } else {
        remove resp.http.Fastly-Module-Enabled;
        remove resp.http.fastly-page-cacheable;
    }

    # debug info. It has to be turned on Magento side
    if (!resp.http.X-Magento-Debug) {
        # remove Varnish/proxy header
        remove resp.http.X-Magento-Debug;
        remove resp.http.X-Magento-Cache-Control;
        remove resp.http.X-Powered-By;
        remove resp.http.Server;
        remove resp.http.X-Varnish;
        remove resp.http.Via;
        remove resp.http.X-Purge-URL;
        remove resp.http.X-Purge-Host;
    }
