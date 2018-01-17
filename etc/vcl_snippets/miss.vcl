    # Deactivate gzip on origin. This is so we can make sure that ESI fragments
    # come uncompressed.
    unset bereq.http.Accept-Encoding;
