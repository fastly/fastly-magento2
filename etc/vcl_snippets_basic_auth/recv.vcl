  if ( table.lookup(magentomodule_basic_auth, req.http.Authorization, "NOTFOUND") == "NOTFOUND" ) {
      error 971;
  }
