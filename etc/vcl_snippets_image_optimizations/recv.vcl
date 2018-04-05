if ( req.url.ext ~ "(?i)^(gif|png|jpg|jpeg|webp)$" ) {

  set req.http.X-Fastly-Imageopto-Api = "fastly";

}
