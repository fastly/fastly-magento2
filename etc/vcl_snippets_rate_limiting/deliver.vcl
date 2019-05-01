# Tarpit Rate limited requests
if ( resp.status == 429 && (req.http.Rate-Limit || resp.http.Fastly-Vary) ) {
  resp.tarpit(std.atoi(table.lookup(magentomodule_config, "tarpit_interval", "5")), 100000);
  set resp.http.Vary = "Cookie";
}

if ( fastly.ff.visits_this_service == 0 ) {
  remove resp.http.Fastly-Vary;
}
