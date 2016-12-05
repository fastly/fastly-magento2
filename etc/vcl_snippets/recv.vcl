    # Fixup for Varnish ESI not dealing with https:// absolute URLs well
    if (req.is_esi_subreq && req.url ~ "/https://([^/]+)(/.*)$") {
        set req.http.Host = re.group.1;
        set req.url = re.group.2;
    }

    # Sort the query arguments
    set req.url = boltsort.sort(req.url);

    if (req.url ~ "^/static/version(\d*/)?(.*)$") {
       set req.url = "/static/" + re.group.2 + "?" + re.group.1;
    }
    
    # User's Cookie may contain some Magento Vary items we should vary on
    if (req.http.cookie:X-Magento-Vary ) {
        set req.http.X-Magento-Vary = req.http.cookie:X-Magento-Vary;
    } else {
        unset req.http.X-Magento-Vary;
    }

    # auth for purging
    if (req.request == "FASTLYPURGE") {
        # extract token signature and expiration
        set req.http.X-Sig = regsub(req.http.X-Purge-Token, "^[^_]+_(.*)", "\1");
        set req.http.X-Exp = regsub(req.http.X-Purge-Token, "^([^_]+)_.*", "\1");

        # validate signature
        if (req.http.X-Sig == regsub(digest.hmac_sha1(req.service_id, req.url.path req.http.X-Exp), "^0x", "")) {

            # use vcl time math to check expiration timestamp
            set req.http.X-Original-Grace = req.grace;
            set req.grace = std.atoi(strftime({"%s"}, now));
            set req.grace -= std.atoi(req.http.X-Exp);

            if (std.atoi(req.grace) > 0) {
                error 410;
            }

            # clean up grace since we used it for time math
            set req.grace = std.atoi(req.http.X-Original-Grace);
            unset req.http.X-Original-Grace;

        } else {
            error 403;
        }

        # cleanup variables
        unset req.http.X-Purge-Token;
        unset req.http.X-Sig;
        unset req.http.X-Exp;
    }

    # set HTTPS header for offloaded TLS
    if (req.http.Fastly-SSL) {
        set req.http.Https = "on";
    }

    if (req.http.Fastly-FF) {
        # disable ESI processing on Origin Shield
        set req.esi = false;
        # Needed for proper handling of stale while revalidated when shielding is involved
        set req.max_stale_while_revalidate = 0s;
    }

    # geoip lookup
    if (req.url ~ "fastlyCdn/geoip/getaction/") {
        # check if GeoIP has been already processed by client. this normally happens before essential cookies are set.
        if (req.http.cookie ~ "(X-Magento-Vary|form_key)=") {
            error 200 "";
        } else {
            # append parameter with country code only if it doesn't exist already
            if ( req.url !~ "country_code=" ) {
                set req.url = req.url "?country_code=" if ( req.http.geo_override, req.http.geo_override, geoip.country_code);
            }
        }
    } else {
        # Per suggestions in https://github.com/sdinteractive/SomethingDigital_PageCacheParams
        # we'll strip out query parameters used in Google AdWords, Mailchimp tracking
        set req.http.Magento-Original-URL = req.url;
        set req.url = querystring.regfilter(req.url, "^(utm_.*|gclid|gdftrk|_ga|mc_.*)");
    }
    

    # static files are always cacheable. remove SSL flag and cookie
    if (req.url ~ "^/(pub/)?(media|static)/.*") {
        unset req.http.Https;
        unset req.http.Cookie;
    }
