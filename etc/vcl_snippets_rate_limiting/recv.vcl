if (####RATE_LIMITED_PATHS####) {
  set req.http.Rate-Limit = "1";
  set req.http.X-Orig-Method = req.method;
  set req.hash_ignore_busy = true;
  if (req.method !~ "^(GET|POST)$") {
    set req.method = "POST";
  }
}
