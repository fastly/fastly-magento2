###############################################################################
#
# Fastly CDN for Magento 2
#
# NOTICE OF LICENSE
#
# This source file is subject to the Fastly CDN for Magento 2 End User License
# Agreement that is bundled with this package in the file LICENSE_FASTLY_CDN.txt.
#
# @copyright   Copyright (c) 2016 Fastly, Inc. (http://www.fastly.com)
# @license     BSD, see LICENSE_FASTLY_CDN.txt
#
###############################################################################

# This is a basic VCL configuration file for Fastly CDN for Magento 2 module.

sub vcl_recv {
#FASTLY recv

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

    # Deactivate gzip
    set req.http.X-Orig-Accept-Encoding = req.http.Accept-Encoding;
    unset req.http.Accept-Encoding;

    # set HTTPS header for offloaded TLS
    if (req.http.Fastly-SSL) {
        set req.http.Https = "on";
    }

    # disable ESI processing on Origin Shield
    if (req.http.Fastly-FF) {
        set req.esi = false;
    }

    # We only deal with GET and HEAD by default
    if (req.request != "GET" && req.request != "HEAD") {
        return (pass);
    }

    # Bypass shopping cart and checkout requests
    if (req.url ~ "/checkout") {
        return (pass);
    }

    # static files are always cacheable. remove SSL flag and cookie
    if (req.url ~ "^/(pub/)?(media|static)/.*") {
        unset req.http.Https;
        unset req.http.Cookie;
    }

    # geoip lookup
    if (req.url ~ "fastlyCdn/geoip/getaction/") {
        # check if GeoIP has been already processed by client. this normally happens before essential cookies are set.
        if (req.http.cookie ~ "(X-Magento-Vary|form_key)=") {
            error 200 "";
        } else {
            # append parameter with country code
            set req.url = req.url "?country_code=" geoip.country_code;
        }
    }

    return(lookup);
}

sub vcl_fetch {

    if (req.url ~ "^/(pub/)?(media|static)/.*") {
        unset beresp.http.set-cookie;
    }

#FASTLY fetch

    if (beresp.status >= 500) {
        # let SOAP errors pass - better debugging
        if (beresp.http.Content-Type ~ "text/xml") {
            return (deliver);
        }

        if (req.restarts < 1 && (req.request == "GET" || req.request == "HEAD")) {
            restart;
        }
    }

    if (req.restarts > 0 ) {
        set beresp.http.Fastly-Restarts = req.restarts;
    }

    if (beresp.http.Content-Type ~ "text/html" || beresp.http.Content-Type ~ "text/xml") {
        # enable ESI feature for Magento response by default
        esi;
    } else {
        # enable gzip for all static content
        if ((beresp.status == 200 || beresp.status == 404) && (beresp.http.content-type ~ "^(text\/html|application\/x\-javascript|text\/css|application\/javascript|text\/javascript|application\/json|application\/vnd\.ms\-fontobject|application\/x\-font\-opentype|application\/x\-font\-truetype|application\/x\-font\-ttf|application\/xml|font\/eot|font\/opentype|font\/otf|image\/svg\+xml|image\/vnd\.microsoft\.icon|text\/plain|text\/xml)\s*($|;)" || req.url ~ "\.(css|js|html|eot|ico|otf|ttf|json)($|\?)" ) ) {
            # always set vary to make sure uncompressed versions dont always win
            if (!beresp.http.Vary ~ "Accept-Encoding") {
                if (beresp.http.Vary) {
                    set beresp.http.Vary = beresp.http.Vary ", Accept-Encoding";
                } else {
                    set beresp.http.Vary = "Accept-Encoding";
                }
            }
            if (req.http.X-Orig-Accept-Encoding == "gzip") {
                set beresp.gzip = true;
            }
        }
    }

    # cache only successfully responses and 404s
    if (beresp.status != 200 && beresp.status != 301 && beresp.status != 404) {
        set req.http.Fastly-Cachetype = "ERROR";
        set beresp.ttl = 1s;
        set beresp.grace = 5s;
        return (deliver);
    } elsif (beresp.http.Cache-Control ~ "private") {
        set req.http.Fastly-Cachetype = "PRIVATE";
        return (pass);
    }

    if (beresp.http.X-Magento-Debug) {
        set beresp.http.X-Magento-Cache-Control = beresp.http.Cache-Control;
    }

    # validate if we need to cache it and prevent from setting cookie
    # images, css and js are cacheable by default so we have to remove cookie also
    if (beresp.ttl > 0s && (req.request == "GET" || req.request == "HEAD")) {
        unset beresp.http.set-cookie;
        if (req.url !~ "^/(pub/)?(media|static)/.*") {
            set beresp.http.Pragma = "no-cache";
            set beresp.http.Expires = "-1";
            set beresp.http.Cache-Control = "no-store, no-cache, must-revalidate, max-age=0";
            set beresp.grace = 1m;
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

        set beresp.http.X-Surrogate-Key = beresp.http.Surrogate-Key;
        return (deliver);
    }

    return (deliver);
}


sub vcl_hit {
#FASTLY hit

    if (!obj.cacheable) {
        return(pass);
    }
    return(deliver);
}


sub vcl_miss {
#FASTLY miss

    return(fetch);
}

sub vcl_deliver {
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
        remove resp.http.X-Surrogate-Key;
        remove resp.http.X-Magento-Cache-Control;
        remove resp.http.X-Powered-By;
        remove resp.http.Server;
        remove resp.http.X-Varnish;
        remove resp.http.Via;
        remove resp.http.Link;
        remove resp.http.X-Purge-URL;
        remove resp.http.X-Purge-Host;
    }

#FASTLY deliver

    return (deliver);
}


sub vcl_error {
    # workaround for possible security issue
    if (req.url ~ "^\s") {
        set obj.status = 400;
        set obj.response = "Malformed request";
        synthetic "";
        return (deliver);
    }

    # error 200
    if (obj.status == 200) {
        return (deliver);
    }

     set obj.http.Content-Type = "text/html; charset=utf-8";
     set obj.http.Retry-After = "5";
     synthetic {"
<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
    <head>
        <title>"} obj.status " " obj.response {"</title>
    </head>
    <body>
        <h1>Error "} obj.status " " obj.response {"</h1>
        <p>"} obj.response {"</p>
        <h3>Guru Meditation:</h3>
        <p>XID: "} req.xid {"</p>
        <hr>
        <p>Fastly CDN server</p>
    </body>
</html>
"};

#FASTLY error
}

sub vcl_pass {
#FASTLY pass
}

sub vcl_hash {
    set req.hash += req.http.Https;
    set req.hash += req.http.host;
    set req.hash += req.url;

    if (req.http.cookie ~ "X-Magento-Vary=") {
        set req.http.X-Magento-Vary = regsub(req.http.cookie, "^.*?X-Magento-Vary=([^;]+);*.*$", "\1");
        set req.hash += req.http.X-Magento-Vary;
        unset req.http.X-Magento-Vary;
    }
    
    set req.hash += "#####GENERATION#####";

    ### {{ design_exceptions_code }} ###

    return (hash);
}

