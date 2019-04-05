# Tarpit Rate limited requests
if ( resp.status == 429 && req.http.Rate-Limit ) {
  resp.tarpit(5, 100000);
}
