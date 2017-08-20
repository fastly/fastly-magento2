    # workaround for possible security issue
    if (req.url ~ "^\s") {
        set obj.status = 400;
        set obj.response = "Malformed request";
        synthetic "";
        return (deliver);
    }

    /* handle 503s */
    if (obj.status >= 500 && obj.status < 600) {

        /* deliver stale object if it is available */
        if (stale.exists) {
            return(deliver_stale);
        }
    }
