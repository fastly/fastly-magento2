    # Send no cache headers to end users for non-static content created by Magento
    if (resp.http.X-Magento-Tags && fastly.ff.visits_this_service == 0 ) {
        set resp.http.Cache-Control = "no-store, no-cache, must-revalidate, max-age=0";
    }

    # Execute only on the edge nodes
    if ( fastly.ff.visits_this_service == 0 ) {
        # Remove X-Magento-Vary and HTTPs Vary served to the user
        set resp.http.Vary = regsub(resp.http.Vary, "X-Magento-Vary,Https", "Cookie");
        remove resp.http.X-Magento-Tags;
    }

    # Add an easy way to see whether custom Fastly VCL has been uploaded
    if ( req.http.Fastly-Debug ) {
        set resp.http.Fastly-Magento-VCL-Uploaded = "1.2.119";
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
