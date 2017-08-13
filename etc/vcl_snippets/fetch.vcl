    /* handle 5XX (or any other unwanted status code) */
    if (beresp.status >= 500 && beresp.status < 600) {

        # let SOAP errors pass - better debugging
        if (beresp.http.Content-Type ~ "text/xml") {
            return (deliver);
        }

        /* deliver stale if the object is available */
        if (stale.exists) {
        return(deliver_stale);
        }

        if (req.restarts < 1 && (req.request == "GET" || req.request == "HEAD")) {
        restart;
        }

        /* else go to vcl_error to deliver a synthetic */
        error 503;
    }

    # Remove Set-Cookies from responses for static content
    # to match the cookie removal in recv.
    if (req.url ~ "^/(pub/)?(media|static)/") {
        unset beresp.http.set-cookie;

        # Set a short TTL for 404's since those can be temporary during the site build/index
        if (beresp.status == 404) {
            set beresp.ttl = 300s;
        }

    }

    if (req.restarts > 0 ) {
        set beresp.http.Fastly-Restarts = req.restarts;
    }

    if (beresp.http.Content-Type ~ "text/(html|xml)") {
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
        # enable gzip for all static content
        if (http_status_matches(beresp.status, "200,404") && (beresp.http.content-type ~ "^(application\/x\-javascript|text\/css|application\/javascript|text\/javascript|application\/json|application\/vnd\.ms\-fontobject|application\/x\-font\-opentype|application\/x\-font\-truetype|application\/x\-font\-ttf|application\/xml|font\/eot|font\/opentype|font\/otf|image\/svg\+xml|image\/vnd\.microsoft\.icon|text\/plain)\s*($|;)" || req.url.ext ~ "(?i)(css|js|html|eot|ico|otf|ttf|json)" ) ) {
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

    if (beresp.http.Cache-Control ~ "private") {
        set req.http.Fastly-Cachetype = "PRIVATE";
        return (pass);
    }

    # Cache non-successful responses for 1 second to avoid onslaught of traffic if backend starts
    # spewing 500s. Also set 5 second grace in case backend is timing out or otherwise messed up
    if ( !http_status_matches(beresp.status, "200,203,300,301,302,404,410")) {
        set req.http.Fastly-Cachetype = "ERROR";
        # Remove Set-Cookies so we don't inadvertenly cache them
        unset beresp.http.Set-Cookie;
        set beresp.ttl = 1s;
        set beresp.grace = 5s;
        return (deliver);
    }

    if (beresp.http.X-Magento-Debug) {
        set beresp.http.X-Magento-Cache-Control = beresp.http.Cache-Control;
    }

    # validate if we need to cache it and prevent from setting cookie
    # images, css and js are cacheable by default so we have to remove cookie also
    if (beresp.ttl > 0s && (req.request == "GET" || req.request == "HEAD") && !req.http.x-pass ) {
        unset beresp.http.set-cookie;
        if (req.url !~ "^/(pub/)?(media|static)/.*") {
            set beresp.grace = 86400m;
        }

        # init surrogate keys
        if (beresp.http.X-Magento-Tags) {
            set beresp.http.Surrogate-Key = beresp.http.X-Magento-Tags " text";
        } else {
            set beresp.http.Surrogate-Key = "text";
        }

        # set surrogate keys by content type
        if (beresp.http.Content-Type ~ "image") {
            set beresp.http.Surrogate-Key = "image";
        } elsif (beresp.http.Content-Type ~ "script") {
            set beresp.http.Surrogate-Key = "script";
        } elsif (beresp.http.Content-Type ~ "css") {
            set beresp.http.Surrogate-Key = "css";
        }
        return (deliver);
    }
