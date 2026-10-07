if (####RATE_LIMITED_PATHS####) {
  if (req.http.Fastly-Client-Ip !~ maint_allowlist) {
      set req.http.Rate-Limit = "1";
      set req.http.X-Orig-Method = req.method;
      set req.hash_ignore_busy = true;
      if (req.method !~ "^(GET|POST)$") {
        set req.method = "POST";
      }
  }
}
if (client.geo.country_code) {
    set req.http.client-geo-country = client.geo.country_code;
}

