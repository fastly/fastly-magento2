# If we are about to serve a 503 we need to restart then in vcl_recv error out to
# get the holding page
if ( resp.status == 503 && !req.http.ResponseObject ) { 
    set req.http.ResponseObject = "970";
    restart;
}
