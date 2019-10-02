    # Deactivate gzip on origin. This is so we can make sure that ESI fragments
    # come uncompressed.
    unset bereq.http.Accept-Encoding;

    # Send VCL version uploaded to the backend
    set bereq.http.Fastly-Magento-VCL-Uploaded = "1.2.119";
