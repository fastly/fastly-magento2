    # VCL required to support maintenance mode. Don't maintenance mode admin pages and supporting assets
    if (table.lookup(magentomodule_config, "allow_super_users_during_maint", "0") == "1" &&
        !req.http.Fastly-Client-Ip ~ maint_allowlist &&
        !req.url ~ "^/(index\.php/)?####ADMIN_PATH####/" &&
        !req.url ~ "^/pub/static/") {

        # If we end up here after a restart and there is a ResponseObject it means we got here after error
        # page VCL restart. We shouldn't touch it. Otherwise return a plain 503 error page
        if ( req.restarts > 0 && req.http.ResponseObject ) {
            set req.http.ResponseObject = "970";
        } else {
            error 503 "Maintenance mode";
        }
    }

    # Fixup for Varnish ESI not dealing with https:// absolute URLs well
    if (req.is_esi_subreq && req.url ~ "/https://([^/]+)(/.*)$") {
        set req.http.Host = re.group.1;
        set req.url = re.group.2;
    }

    unset req.http.x-long-cache;

    # We want to force long cache times on any of the versioned assets
    if (req.url.path ~ "^/static/version\d*/") {
       set req.http.x-long-cache = "1";
    }
    
    # User's Cookie may contain some Magento Vary items we should vary on
    if (req.http.cookie:X-Magento-Vary) {
        set req.http.X-Magento-Vary = req.http.cookie:X-Magento-Vary;
    } else {
        unset req.http.X-Magento-Vary;
    }

    ############################################################################################################
    # Following code block controls purge by URL. By default we want to protect all URL purges. In general this
    # is addressed by adding Fastly-Purge-Requires-Auth request header in vcl_recv however this runs the risk of
    # exposing API tokens if user attempts to purge non-https URLs. For this reason inside the Magento module
    # we use X-Purge-Token. Unfortunately this breaks purge from the Fastly UI. Therefore in the next code block
    # we check for presence of X-Purge-Token. If it's not present we force the Fastly-Purge-Requires-Auth
    if (req.request == "FASTLYPURGE") {
        # extract token signature and expiration
        if (req.http.X-Purge-Token && req.http.X-Purge-Token ~ "^([^_]+)_(.*)" ) {

            declare local var.X-Exp STRING;
            declare local var.X-Sig STRING;
            /* extract token expiration and signature */
            set var.X-Exp = re.group.1;
            set var.X-Sig = re.group.2;

            /* validate signature */
            if (var.X-Sig == regsub(digest.hmac_sha1(req.service_id, req.url.path var.X-Exp), "^0x", "")) {
            /* check that expiration time has not elapsed */
                if (time.is_after(now, std.integer2time(std.atoi(var.X-Exp)))) {
                    error 410;
                }
            } else {
                error 403;
            }
        } else {
            set req.http.Fastly-Purge-Requires-Auth = "1";
        }
    }

    # set HTTPS header for offloaded TLS
    if (req.http.Fastly-SSL) {
        set req.http.Https = "on";
    }

    if (fastly.ff.visits_this_service > 0) {
        # disable ESI processing on Origin Shield
        set req.esi = false;
        # Needed for proper handling of stale while revalidated when shielding is involved
        set req.max_stale_while_revalidate = 0s;
    }

    # Make sure we lookup end user geo not shielding. More at https://docs.fastly.com/vcl/geolocation/#using-geographic-variables-with-shielding
    set client.geo.ip_override = req.http.Fastly-Client-IP;

    # geoip lookup
    if (req.url.path ~ "fastlyCdn/geoip/getaction/") {
        # check if GeoIP has been already processed by client. this normally happens before essential cookies are set.
        if (req.http.cookie:X-Magento-Vary || req.http.cookie:form_key) {
            error 980 "GeoIP already processed";
        } else {
            # append parameter with country code only if it doesn't exist already
            if ( req.url.qs !~ "country_code=" ) {
                set req.url = querystring.set(req.url, "country_code", if ( req.http.geo_override, req.http.geo_override, client.geo.country_code));
            }
        }
    } else {
        # Per suggestions in https://github.com/sdinteractive/SomethingDigital_PageCacheParams
        # we'll strip out query parameters used in Google AdWords, Mailchimp tracking by default
        # and allow custom parameters to be set. List of parameters is configurable in admin
        set req.http.Magento-Original-URL = req.url;
        # Change the list of ignored parameters by configuring them in the Advanced section
        set req.url = querystring.regfilter(req.url, "^(####QUERY_PARAMETERS####)");
    }

    # Don't allow clients to force a pass
    if (req.restarts == 0) {
        unset req.http.x-pass;
        unset req.http.Rate-Limit;
    }
    
    # Pass on checkout URLs. Because it's a snippet we want to execute this after backend selection so we handle it
    # in the request condition
    if (!req.http.x-long-cache && req.url ~ "/(catalogsearch|checkout|customer/section/load)") {
        set req.http.x-pass = "1";
    # Pass all admin actions
    # ####ADMIN_PATH#### is replaced with value of frontName from app/etc/env.php
    } else if ( req.url ~ "^/(index\.php/)?####ADMIN_PATH####/" ) {
        set req.http.x-pass = "1";
    } else {
        # Sort the query arguments to increase cache hit ratio with query arguments that
        # may be out od order. In case you want to restore the unsorted URL add a snippet
        # after this one that sets req.url to req.http.Magento-Original-URL
        if ( !req.http.x-pass ) {
            set req.url = boltsort.sort(req.url);
        }
    }

    # static files are always cacheable. remove SSL flag and cookie
    if (req.http.x-long-cache || req.url ~ "^/(pub/)?(media|static)/.*") {
        unset req.http.Https;
        unset req.http.Cookie;
    }

    unset req.http.graphql;
    # GraphQL special headers handling because this area doesn't rely on X-Magento-Vary cookie
    if (req.request == "GET" && req.url.path ~ "/graphql" && req.url.qs ~ "query=") {
        if ( req.http.Authorization ~ "^Bearer" ) {
            set req.http.x-pass = "1";
        } else {
            set req.http.graphql = "1";
            if (req.http.Store) {
                set req.http.X-Magento-Vary = req.http.Store;
            }
            if (req.http.Content-Currency) {
                if (req.http.X-Magento-Vary) {
                    set req.http.X-Magento-Vary = req.http.X-Magento-Vary req.http.Content-Currency;
                } else {
                    set req.http.X-Magento-Vary = req.http.Content-Currency;
                }
            }
        }
    }
