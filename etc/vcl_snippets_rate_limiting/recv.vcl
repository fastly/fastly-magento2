if (####RATE_LIMITED_PATHS####) {
   set req.http.X-Pass = "1";
   set req.http.Rate-Limit = "1";
   return(lookup);
}