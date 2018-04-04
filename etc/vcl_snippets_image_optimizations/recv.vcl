if ( req.url.ext ~ "(?i)^(gif|png|jpg|jpeg|webp)$" ) {

  set req.http.X-Fastly-Imageopto-Api = "fastly";

  if (req.url.qs != "") {
    set req.url = req.url.path "?" req.url.qs "&auto=webp";
  } else {
    set req.url = req.url.path "?auto=webp";
  }
}
