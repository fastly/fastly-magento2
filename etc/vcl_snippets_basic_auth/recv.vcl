  # Check Basic auth against a table. /admin URLs are not basic auth protected to avoid the possibility of people
  # locking themselves out
  if ( table.lookup(magentomodule_basic_auth, regsub(req.http.Authorization, "^Basic ", ""), "NOTFOUND") == "NOTFOUND" &&
      !req.url ~ "^/(index\.php/)?admin(_.*)?/" &&
      !req.url ~ "^/pub/static/" ) {
      error 971;
  }
