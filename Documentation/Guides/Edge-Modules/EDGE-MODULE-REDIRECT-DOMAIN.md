# Fastly Edge Modules - Redirect one domain to another

This guide will show how to configure domain redirection from one to another. This particular feature is useful to redirect apex/naked
domains to www e.g. domain.com => www.domain.com.

Before you can use Fastly Edge Modules you need to [make sure they are enabled](https://github.com/fastly/fastly-magento2/blob/master/Documentation/Guides/Edge-Modules/EDGE-MODULES.md)

When you click on the configuration you will be prompted with a screen like this

![Fastly Edge Module Cloud Sitemap rewrites configuration](../../images/guides/edge-modules/edge-module-redirect-one-domain-to-another.png "Fastly Edge Module Cloud Sitemap rewrites")

You can specify multiple domain redirects by clicking *Add Group* button

## Configurable options

### Incoming Domain/Host

Incoming domain e.g. `domain.com`

### Destination domain/host

Destination domain/host e.g. `www.domain.com`

### Ignore path

Strip incoming path and set it to /. Default only rewrites host retaining the path e.g. http://domain.com/category is redirected to https://www.domain.com/category

## Enabling

After any change to the settings you need to click Upload as that will activate the functionality you configured.

## Technical details

Following VCL snippets are being uploaded

### Ignore path

Snippet Type: vcl_recv

```vcl
if (req.http.host == "domain.com") {
  set req.http.host = "www.domain.com";
  set req.url = "/";
  error 801;
}
```

### Leave path alone

Snippet Type: vcl_recv

```vcl
if (req.http.host == "domain.com") {
  set req.http.host = "www.domain.com";
  error 801;
}
```

