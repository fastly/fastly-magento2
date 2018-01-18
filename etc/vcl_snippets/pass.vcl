    # Deactivate gzip on origin. This is so we can make sure that ESI fragments
    # come uncompressed. X-Long-Cache objects can be left untouched.
    if ( !req.http.x-long-cache ) {
        unset bereq.http.Accept-Encoding;
    }

    # Increase first byte timeouts for the admin path to 3 minutes by default or a custom value
    if ( req.url ~ "^/(index\.php/)?####ADMIN_PATH####/" ) {

      set bereq.first_byte_timeout = ####ADMIN_PATH_TIMEOUT####s;


    }
