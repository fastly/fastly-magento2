    # Deactivate gzip on origin. This is so we can make sure that ESI fragments
    # come uncompressed.
    unset bereq.http.Accept-Encoding;

    # Increase first byte timeouts for /admin* URLs to 3 minutes
    # ####ADMIN_PATH#### is replaced with value of frontName from app/etc/env.php
    if ( req.url ~ "^/(index\.php/)?####ADMIN_PATH####/" ) {

      set bereq.first_byte_timeout = 180s;


    }
