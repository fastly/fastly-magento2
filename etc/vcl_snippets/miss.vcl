    # Deactivate gzip on origin. This is so we can make sure that ESI fragments
    # come uncompressed. X-Long-Cache objects can be left untouched.
    if ( !req.http.x-long-cache ) {
        unset bereq.http.Accept-Encoding;
    }
