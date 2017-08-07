  # Check Basic auth against a table
  if ( table.lookup(magentomodule_basic_auth, regsub(req.http.Authorization, "^Basic ", ""), "NOTFOUND") == "NOTFOUND" ) {
      error 971;
  }