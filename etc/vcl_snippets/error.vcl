    /* handle 503s */
    if (obj.status >= 500 && obj.status < 600) {

        /* deliver stale object if it is available */
        if (stale.exists) {
            return(deliver_stale);
        }
    }

    # Return empty response in case fastlyCdn/geoip/getaction/ has been processed already
    if (obj.status == 980) {
        set obj.status = 200;
        synthetic {""};
        return (deliver);
    }

    if (obj.status == 750) {
        set obj.http.Location = "https://" + req.http.host + req.url;
        set obj.status = 301;
        set obj.response = "Moved Permanently";
        synthetic {""};
        return (deliver);
    }