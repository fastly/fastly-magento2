# Sets up synthetic responses
if ( req.url.path == "/.fastly-image-optimization-test-parameters" ) {
  if ( req.url.qs == "" ) {
    error 798;
  } else {
    set req.url = querystring.filter_except(req.url, "quality" + querystring.filtersep() + "bgcolor");
    error 799;
  }

}

# Enable IO only for users with the cookie
if ( req.http.Cookie:fastly-io-test && req.url.ext ~ "(?i)^(gif|png|jpg|jpeg|webp)$") {

  set req.http.X-Fastly-Imageopto-Api = "fastly";

  if (req.url.qs == "") {
    set req.url = req.url "?" req.http.Cookie:fastly-io-test "&auto=webp";
  } else {
    set req.url = req.url "&" req.http.Cookie:fastly-io-test "&auto=webp";
  }
  
}