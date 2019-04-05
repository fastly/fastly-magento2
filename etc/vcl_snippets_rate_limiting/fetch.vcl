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
