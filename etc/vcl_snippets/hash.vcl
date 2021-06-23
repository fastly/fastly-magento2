    # Fastly should cache on X-Magento-Cache-Id if available, which has a bunch of variations so it should be part of the key and not a Vary factor
    if (req.http.graphql && req.http.X-Magento-Cache-Id) {
        set req.hash += req.http.X-Magento-Cache-Id;
    }
    return(hash);
