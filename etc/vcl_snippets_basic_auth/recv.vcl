  # Check Basic auth against a table. /admin URLs are no basic auth protected to avoid the possibility of people
  # locking themselves out
  if ( table.lookup(magentomodule_basic_auth, regsub(req.http.Authorization, "^Basic ", ""), "NOTFOUND") == "NOTFOUND" &&
      req.url !~ "^/(index\.php/)?admin(_.*)?/" ) {
      error 971;
  }
