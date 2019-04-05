if (req.http.Rate-Limit) {
   set req.hash += client.ip;
   return(hash);
}