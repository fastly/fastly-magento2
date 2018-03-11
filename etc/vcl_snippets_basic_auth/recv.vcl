  # Check Basic auth against a table. /admin URLs are not basic auth protected to avoid the possibility of people
  # locking themselves out. /oauth and /rest have their own auth so we can skip Basic Auth on them as well
  if ( table.lookup(magentomodule_basic_auth, regsub(req.http.Authorization, "^Basic ", ""), "NOTFOUND") == "NOTFOUND" &&
      !req.url ~ "^/(index\.php/)?####ADMIN_PATH####/" &&
      !req.url ~ "^/(index\.php/)?(rest|oauth)/" &&
      !req.url ~ "^/pub/static/" ) {
      error 971;
  }
