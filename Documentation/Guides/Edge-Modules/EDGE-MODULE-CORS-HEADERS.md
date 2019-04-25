# Fastly Edge Modules - CORS Headers

This guide will show how to configure [CORS Headers](https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS) using Fastly Edge Modules. Before you can use Fastly
Edge Modules you need to [make sure they are enabled](https://github.com/fastly/fastly-magento2/blob/master/Documentation/Guides/Edge-Modules/EDGE-MODULES.md)

When you click on the configuration you will be prompted with a screen like this

![Fastly Edge Module CORS Headers configuration](../../images/guides/edge-modules/edge-module-cors.png "Fastly Edge Module CORS Headers configuration")


Purpose of this module is to add CORS headers on responses sent to the end user. CORS headers
will only be added to 

- Requests with the Origin request headers

Please note they *WILL NOT* be added

- to any backend responses that contain Access-Control-Allow-Origin and/or Access-Control-Allow-Method response headers

## Configurable options

### Origins Allowed

This allows you specify if you want return CORS headers to anyone or specific origins. For example selecting Allow anyone will set

```
Access-Control-Allow-Origin: *
```

This allows any origin to use your service. 

Alternatively if you choose _Regex matching origins that are allowed to access this service_ origin will have to match the regular
expression. For example specifying the regular expression of *mydomain.com* will resolve into 

```https?://mydomain.com```

If you choose this option make sure you insert regex into the *Regex Matching Origin* field at the bottom of the form.

### Allowed HTTP Methods


Allowed HTTP methods specifies which methods you support. It will set following response header 

```
Access-Control-Allow-Methods: GET POST PUT
```

### Regex Matching Origin

This field is only used if Origins Allowed dropdown selects _Regex matching origins that are allowed to access this service.
Only enter regex matching a domain as we already prepend HTTP method regex.


## Enabling

After any change to the settings you need to click Upload as that will upload require VCL code to Fastly.

## Technical details

Following VCL is being uploaded in Allow Anyone case

Snippet Type: vcl_deliver

```vcl
if (req.http.Origin && !resp.http.Access-Control-Allow-Origin && !resp.http.Access-Control-Allow-Methods) {
    set resp.http.Access-Control-Allow-Origin = "*";
    set resp.http.Access-Control-Allow-Methods = "GET,HEAD,POST";
  }
```

Following is uploaded in case of a regex matching a set of origins

```vcl
if (req.http.Origin && !resp.http.Access-Control-Allow-Origin && !resp.http.Access-Control-Allow-Methods) {
    if ( req.http.Origin ~ "^https?://mydomain.com" ) {
      set resp.http.Access-Control-Allow-Origin = req.http.origin;
    }
    set resp.http.Access-Control-Allow-Methods = "GET,HEAD,POST";
  }
```
