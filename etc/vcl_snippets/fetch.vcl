    /* handle 5XX (or any other unwanted status code) */
    if (beresp.status >= 500 && beresp.status < 600) {

        /* deliver stale if the object is available */
        if (stale.exists) {
            return(deliver_stale);
        }

        if (req.restarts < 1 && (req.request == "GET" || req.request == "HEAD")) {
            restart;
        }

        /* else go to vcl_error to deliver a synthetic */
        error beresp.status beresp.response;
    }

    # Remove Set-Cookies from responses for static content
    # to match the cookie removal in recv.
    if (req.http.x-long-cache || req.url ~ "^/(pub/)?(media|static)/") {
        unset beresp.http.set-cookie;

        # Set a short TTL for 404's since those can be temporary during the site build/index
        if (beresp.status == 404) {
            set beresp.ttl = 300s;
        }

    }

    # Force caching for signed cached assets.
    if (req.http.x-long-cache) {
        set beresp.ttl = 31536000s;
        set beresp.http.Cache-Control = "max-age=31536000, immutable";
    }

    # All the Magento responses should emit X-Esi headers
    if (beresp.http.x-esi) {
        # enable ESI feature for Magento response by default
        esi;
        if (!beresp.http.Vary ~ "X-Magento-Vary,Https") {
            if (beresp.http.Vary) {
                set beresp.http.Vary = beresp.http.Vary ",X-Magento-Vary,Https";
            } else {
                set beresp.http.Vary = "X-Magento-Vary,Https";
            }
        }
        # Since varnish doesn't compress ESIs we need to hint to the HTTP/2 terminators to
        # compress it
        set beresp.http.x-compress-hint = "on";
    } else {
        # enable gzip for all static content except
        if ( http_status_matches(beresp.status, "200,404") && (beresp.http.content-type ~ "^(application\/x\-javascript|text\/css|text\/html|application\/javascript|text\/javascript|application\/json|application\/vnd\.ms\-fontobject|application\/x\-font\-opentype|application\/x\-font\-truetype|application\/x\-font\-ttf|application\/xml|font\/eot|font\/opentype|font\/otf|image\/svg\+xml|image\/vnd\.microsoft\.icon|text\/plain)\s*($|;)" || req.url.ext ~ "(?i)(css|js|html|eot|ico|otf|ttf|json)" ) ) {
            # always set vary to make sure uncompressed versions dont always win
            if (!beresp.http.Vary ~ "Accept-Encoding") {
                if (beresp.http.Vary) {
                    set beresp.http.Vary = beresp.http.Vary ", Accept-Encoding";
                } else {
                    set beresp.http.Vary = "Accept-Encoding";
                }
            }
            if (req.http.Accept-Encoding == "gzip") {
                set beresp.gzip = true;
            }
        }
    }

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

    # Just in case the Request Setting for x-pass is missing
    if (req.http.x-pass) {
        return (pass);
    }

    # Varnish sets default TTL if none of these are present
    if (!beresp.http.Expires && !beresp.http.Surrogate-Control ~ "max-age" && !beresp.http.Cache-Control ~ "(s-maxage|max-age)") {
        set beresp.ttl = 0s;
    }

    # validate if we need to cache it and prevent from setting cookie
    # images, css and js are cacheable by default so we have to remove cookie also
    if (beresp.ttl > 0s && (req.request == "GET" || req.request == "HEAD") && !req.http.x-pass ) {
        unset beresp.http.set-cookie;

        # init surrogate keys
        if (beresp.http.X-Magento-Tags) {
            set beresp.http.Surrogate-Key = beresp.http.X-Magento-Tags " text";
        } else {
            set beresp.http.Surrogate-Key = "text";
        }

        # set surrogate keys by content type if they are image/script or CSS
        if (beresp.http.Content-Type ~ "(image|script|css)") {
            set beresp.http.Surrogate-Key = re.group.1;
        }
    }
