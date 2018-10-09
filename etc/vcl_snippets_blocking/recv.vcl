   # Make sure we lookup end user geo not shielding. More at https://docs.fastly.com/vcl/geolocation/#using-geographic-variables-with-shielding
   set client.geo.ip_override = req.http.Fastly-Client-IP;
   if (####BLOCKED_ITEMS####) {
      error 405 "Not allowed";
   }