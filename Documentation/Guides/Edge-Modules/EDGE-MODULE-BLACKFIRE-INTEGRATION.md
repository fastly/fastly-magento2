# Fastly Edge Modules - Blackfire integration 

This module will enable Blackfire integration. It's available in module version 1.2.103+. It's based on instructions
from [Blackfire reference guide on bypassing reverse proxy cache and CDNs](https://blackfire.io/docs/reference-guide/configuration#bypassing-reverse-proxy-cache-and-content-delivery-networks-cdn).

Before you can use Fastly Edge Modules you need to [make sure they are enabled](https://github.com/fastly/fastly-magento2/blob/master/Documentation/Guides/Edge-Modules/EDGE-MODULES.md) and that you have selected the Blackfire integration module.

When you click on the configuration you will be prompted with a screen like this

![Fastly Edge Module Blackfire configuration](../../images/guides/edge-modules/edge-module-blackfire.png "Fastly Edge Module Blackfire configuration")

There are no configurable options. All you need to do is click Upload.

## Enabling

After any change to the settings you need to click Upload as that will upload require VCL code to Fastly.

## Technical details

Following VCL will be uploaded

Snippet Type: vcl_recv

```vcl
if (req.http.X-Blackfire-Query ) {
    if (req.esi_level > 0) {
        # ESI request should not be included in the profile.
        # Instead you should profile them separately, each one
        # in their dedicated profile.
        # Removing the Blackfire header avoids to trigger the profiling.
        # Not returning let it go trough your usual workflow as a regular
        # ESI request without distinction.
        unset req.http.X-Blackfire-Query;
    } else {
        set req.http.X-Pass = "1";
    }
  }
```
