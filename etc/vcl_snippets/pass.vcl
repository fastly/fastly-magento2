    # Deactivate gzip on origin. This is so we can make sure that ESI fragments
    # come uncompressed.
    unset bereq.http.Accept-Encoding;

    # Increase first byte timeouts for /admin* URLs. By default it's set to 3 minutes
    # however it can be adjusted by adjusting the Admin path timeout under Advanced configs
    # ####ADMIN_PATH#### is replaced with value of frontName from app/etc/env.php
    if ( req.url ~ "^/(index\.php/)?####ADMIN_PATH####/" ) {

      set bereq.first_byte_timeout = ####ADMIN_PATH_TIMEOUT####s;

    }

    # Send VCL version uploaded to the backend
    set bereq.http.Fastly-Magento-VCL-Uploaded = "1.2.119";
