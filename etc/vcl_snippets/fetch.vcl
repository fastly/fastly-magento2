    # If we are doing a bypass for Magento Tester return early as not to affect any headers
    if ( req.http.bypass-secret ) {
        set beresp.ttl = 0s;
        return(pass);
    }

    /* handle 5XX (or any other unwanted status code) */
    if (beresp.status >= 500 && beresp.status < 600) {

        /* deliver stale if the object is available */
        if (stale.exists) {
            return(deliver_stale);
        }
    }

    # Remove Set-Cookies from responses for static content to match the cookie removal in recv.
    if (req.http.x-long-cache || req.url ~ "^/(pub/)?(media|static)/") {
        unset beresp.http.set-cookie;

        # Set a short TTL for 404's since those can be temporary during the site build/index
        if (beresp.status == 404) {
            set beresp.ttl = 300s;
            set beresp.http.Cache-Control = "max-age=0";
        } else if (req.http.x-long-cache) {
            # Force caching for signed cached assets.
            set beresp.ttl = 31536000s;
            # Add immutable as it avoids IMS and INM revalidations
            set beresp.http.Cache-Control = "max-age=31536000, immutable";
            # Remove HTTP/1.0 caching headers as they may mess with browser memory caches
            unset beresp.http.Pragma;
            unset beresp.http.Expires;
        }
    }

    # Fix Vary Header in some cases. In 99.9% of cases Varying on User-Agent is counterproductive
    # https://www.varnish-cache.org/trac/wiki/VCLExampleFixupVary
    if (beresp.http.Vary ~ "User-Agent") {
        unset beresp.http.Vary:User-Agent;
        if (beresp.http.Vary == "") {
            unset beresp.http.Vary;
        }
    }

    # All the Magento responses should emit X-Esi headers
    if (beresp.http.x-esi) {
        # enable ESI feature for Magento response by default
        esi;
    } else {
        # enable gzip for all static content except
        if ( http_status_matches(beresp.status, "200,404") && (beresp.http.content-type ~ "^(application/x\-javascript|text/css|text/html|application/javascript|text/javascript|application/json|application/vnd\.ms\-fontobject|application/x\-font\-opentype|application/x\-font\-truetype|application/x\-font\-ttf|application/xml|font/eot|font/opentype|font/otf|image/svg\+xml|image/vnd\.microsoft\.icon|text/plain)\s*($|;)" || req.url.ext ~ "(?i)(css|js|html|eot|ico|otf|ttf|json)" ) ) {
            # always set vary to make sure uncompressed versions dont always win
            if (!beresp.http.Vary ~ "Accept-Encoding") {
                set beresp.http.Vary:Accept-Encoding = "";
            }
            if (req.http.Accept-Encoding == "br") {
                set beresp.brotli = true;
            } else if (req.http.Accept-Encoding == "gzip") {
                set beresp.gzip = true;
            }
        }
    }

    # Add Varying on Store and Content-Currency for GraphQl, X-Magento-Vary otherwise
    if (req.http.graphql) {
        set beresp.http.Vary:Store = "";
        set beresp.http.Vary:Content-Currency = "";
    } else if (beresp.http.Content-Type ~ "text/(html|xml)") {
        set beresp.http.Vary:X-Magento-Vary = "";
        set beresp.http.Vary:X-Store-Cookie = "";
        set beresp.http.Vary:Https = "";
    }

    # Just in case the Request Setting for x-pass is missing
    if (req.http.x-pass) {
        return (pass);
    }

    # We are extending the default Fastly behavior to include no-cache/no-store in addition to private
    if (beresp.http.Cache-Control ~ "private|no-cache|no-store") {
        set req.http.Fastly-Cachetype = "PRIVATE";
        return (pass);
    }

    if (beresp.http.X-Magento-Debug) {
        set beresp.http.X-Magento-Cache-Control = beresp.http.Cache-Control;
    }

    # Never cache 302s
    if (beresp.status == 302) {
        return (pass);
    }

    if (beresp.http.x-amz-request-id) {
        # If assets are coming from Amazon they may have no Cache-Control headers which may make them uncacheable

        # If the object is coming with no Expires, Surrogate-Control or Cache-Control headers we assume it's a misconfiguration
        # and we will not cache it. This is to prevent inadventently caching private data
    } else if (!beresp.http.Expires && !beresp.http.Surrogate-Control ~ "max-age" && !beresp.http.Cache-Control ~ "(s-maxage|max-age)") {
        # Varnish sets default TTL if none of the headers above are present. If not set we want to make sure we don't cache it
        set beresp.ttl = 3600s;
        return(pass);
    }

    # validate if we need to cache it and prevent from setting cookie
    # images, css and js are cacheable by default so we have to remove cookie also
    if (beresp.ttl > 0s && (req.request == "GET" || req.request == "HEAD") && !req.http.x-pass ) {
        unset beresp.http.set-cookie;

        # init surrogate keys
        if (beresp.http.X-Magento-Tags) {
            set beresp.http.Surrogate-Key = beresp.http.X-Magento-Tags " text";
        } else if (beresp.http.Surrogate-Key && beresp.http.Surrogate-Key !~ "text") {
            set beresp.http.Surrogate-Key = beresp.http.Surrogate-Key " text";
        } else if (!beresp.http.Surrogate-Key) {
            set beresp.http.Surrogate-Key = "text";
        }

        # set surrogate keys by content type if they are image/script or CSS
        if (beresp.http.Content-Type ~ "(image|script|css)") {
            if (beresp.http.Surrogate-Key) {
                set beresp.http.Surrogate-Key = beresp.http.Surrogate-Key " " re.group.1;
            } else {
                set beresp.http.Surrogate-Key = re.group.1;
            }
        }
    }

    # If the cache key in the Magento response doesn't match the one that was sent in the request, don't cache under the request's key
    if (req.http.graphql && req.http.X-Magento-Cache-Id && req.http.X-Magento-Cache-Id != beresp.http.X-Magento-Cache-Id) {
        set beresp.ttl = 3600s;
        return(pass);
    }
