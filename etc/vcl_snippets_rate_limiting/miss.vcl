if (req.http.Rate-Limit) {
  set bereq.method = req.http.X-Orig-Method;
}
