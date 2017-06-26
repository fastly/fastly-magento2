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

    # Remove X-Magento-Vary and HTTPs Vary served to the user
    if ( !req.http.Fastly-FF ) {
        set resp.http.Vary = regsub(resp.http.Vary, "X-Magento-Vary,Https", "Cookie");
    }

    # Add an easy way to see whether custom Fastly VCL has been uploaded
    if ( req.http.Fastly-Debug ) {
        set resp.http.Fastly-Magento-VCL-Uploaded = "1.2.21";
    } else {
        remove resp.http.Fastly-Module-Enabled;
    }
    # debug info
    if (resp.http.X-Magento-Debug) {
        if (obj.hits > 0) {
            set resp.http.X-Magento-Cache-Debug = "HIT";
            set resp.http.X-Magento-Cache-Hits = obj.hits;
        } else {
            set resp.http.X-Magento-Cache-Debug = "MISS";
        }
    } else {
        # remove Varnish/proxy header
        remove resp.http.Age;
        remove resp.http.X-Magento-Debug;
        remove resp.http.X-Magento-Tags;
        remove resp.http.X-Magento-Cache-Control;
        remove resp.http.X-Powered-By;
        remove resp.http.Server;
        remove resp.http.X-Varnish;
        remove resp.http.Via;
        remove resp.http.X-Purge-URL;
        remove resp.http.X-Purge-Host;
    }
