    # Deactivate gzip on origin
    unset bereq.http.Accept-Encoding;

    # Increase first byte timeouts for /admin* URLs to 3 minutes
    if ( req.url ~ "^/(index\.php/)?admin(_.*)?/" ) {

      set bereq.first_byte_timeout = 180s;


    }
