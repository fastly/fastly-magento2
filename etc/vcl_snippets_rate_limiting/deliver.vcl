# Tarpit Rate limited requests
if ( resp.status == 429 && req.http.Rate-Limit ) {
  resp.tarpit(std.atoi(table.lookup(magentomodule_config, "tarpit_interval", "5")), 100000);
}
