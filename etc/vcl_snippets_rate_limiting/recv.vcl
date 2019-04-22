if (####RATE_LIMITED_PATHS####) {
   set req.http.Rate-Limit = "1";
   set req.hash_ignore_busy = true;
}
