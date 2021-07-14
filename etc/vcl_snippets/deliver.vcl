    # If we are doing a bypass for Magento Tester return early as not to affect any headers
    if ( req.http.bypass-secret ) {
        return(deliver);
    }

    # Send no cache headers to end users for non-static content created by Magento
    if (resp.http.X-Magento-Tags && fastly.ff.visits_this_service == 0 ) {
        set resp.http.Cache-Control = "no-store, no-cache, must-revalidate, max-age=0";
    }

    # Make sure GraphQl requests are covered by store and currency
    if (req.http.graphql && resp.http.Vary !~ "(?i)Store,\s*Content-Currency") {
        if (resp.http.Vary) {
            set resp.http.Vary = resp.http.Vary ",Store,Content-Currency";
        } else {
            set resp.http.Vary = "Store,Content-Currency";
        }
    }

    # Execute only on the edge nodes
    if ( fastly.ff.visits_this_service == 0 ) {
        if ( req.http.graphql ) {
            set resp.http.Vary = resp.http.Vary ",Authorization";
            if ( resp.http.X-Magento-Cache-Id ) {
                # GraphQl queries should be cached by the browser under X-Magento-Cache-Id if available
                set resp.http.Vary = resp.http.Vary ",X-Magento-Cache-Id";
            }
        } else {
            # Remove X-Magento-Vary and HTTPs Vary served to the user
            set resp.http.Vary = regsub(resp.http.Vary, "(?i)X-Magento-Vary,Https", "Cookie");
        }
        # Since varnish doesn't compress ESIs we need to hint to the HTTP/2 terminators to
        # compress it and we only want to do this on the edge nodes
        if (resp.http.x-esi) {
            set resp.http.x-compress-hint = "on";
        }
        remove resp.http.X-Magento-Tags;
    }

    # Add an easy way to see whether custom Fastly VCL has been uploaded
    if ( req.http.Fastly-Debug ) {
        set resp.http.Fastly-Magento-VCL-Uploaded = "1.2.160";
    } else {
        remove resp.http.Fastly-Module-Enabled;
        remove resp.http.fastly-page-cacheable;
    }

    # X-Magento-Debug header is exposed when developer mode is activated in Magento
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
