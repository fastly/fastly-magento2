    unset req.http.graphql;
    # Fastly should cache on X-Magento-Cache-Id if available, which has a bunch of variations so it should be part of the key and not a Vary factor
    if (req.request == "GET" && req.url.path ~ "/graphql" && req.url.qs ~ "query=") {
        set req.http.graphql = "1";
        if ( req.http.X-Magento-Cache-Id ) {
            set req.hash += req.http.X-Magento-Cache-Id;
        }
    }
    return(hash);
