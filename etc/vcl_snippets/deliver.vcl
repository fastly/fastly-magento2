    if (resp.status >= 500 && resp.status < 600) {
        /* restart if the stale object is available */
        if (stale.exists) {
            restart;
        }
    }

    # Send no cache headers to end users for non-static content. Also make sure
    # we only set this on the edge nodes and not on shields
    if (req.url !~ "^/(pub/)?(media|static)/.*" && !req.http.Fastly-FF ) {
        set resp.http.Pragma = "no-cache";
        set resp.http.Cache-Control = "no-store, no-cache, must-revalidate, max-age=0";
    }

    # Execute only on the edge nodes
    if ( !req.http.Fastly-FF ) {
        # Remove X-Magento-Vary and HTTPs Vary served to the user
        set resp.http.Vary = regsub(resp.http.Vary, "X-Magento-Vary,Https", "Cookie");
        remove resp.http.X-Magento-Tags;
    }

    # Add an easy way to see whether custom Fastly VCL has been uploaded
    if ( req.http.Fastly-Debug ) {
        set resp.http.Fastly-Magento-VCL-Uploaded = "1.2.51";
    } else {
        remove resp.http.Fastly-Module-Enabled;
        remove resp.http.fastly-page-cacheable;
    }

    # debug info
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
