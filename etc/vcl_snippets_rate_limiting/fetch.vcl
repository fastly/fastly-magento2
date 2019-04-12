    # VCL support for Rate limiting. Rate limiting needs to be turned on through the UI
    if ( req.http.Rate-Limit ) {
        if ( beresp.status == 429 ) {
            set beresp.cacheable = true;
            remove beresp.http.Vary;
            return(deliver);
        } else {
            set beresp.cacheable = false;
            return(pass);
        }
    }

    # If we get Fastly-Vary it means it was set by the Crawler Module. Unfortunately magento overwrites
    # Vary headers so we had to set our own Fastly-Vary header we set here.
    if ( beresp.status == 429 && beresp.http.Fastly-Vary ) {
        set beresp.http.Vary = beresp.http.Fastly-Vary;
    }
