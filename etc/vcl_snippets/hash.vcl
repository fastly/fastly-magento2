    if (req.http.graphql) {
        # GraphQL should cache on X-Magento-Cache-Id if available, which has a bunch of variations so it should be part of the key and not a Vary factor
        if (req.http.X-Magento-Cache-Id) {
            set req.hash += req.http.X-Magento-Cache-Id;
        }
        # When the frontend stops sending the auth token, make sure users stop getting results cached for logged-in users
        # Unfortunately we don't yet have a way to authenticate before hitting the cache, so this is as close as we can get for now
        if ( req.http.Authorization ~ "^Bearer" ) {
            set req.hash += "Authorized";
        }

        set req.hash += req.url;
        set req.hash += req.http.Host;
        set req.hash += req.http.Fastly-SSL;
        set req.hash += req.vcl.generation;
        return (hash);
    }
