# Fastly Edge Modules

This guide will show how to configure Fastly Edge Modules. Fastly Edge Modules
is a flexible framework that allows definition of UI components and associated VCL
code through a template.

In order to enable Fastly Edge Modules you will need to turn them on by going to

```
Magento admin > Stores > Configuration > Advanced > System > Full Page Cache > Fastly Configuration > Advanced
```

Find *Enable Fastly Edge Modules* and select Yes then press Save Config at top right. After enabling you 
should now see Fastly Edge Module sub-menu like this

![Fastly Edge Modules Main Screen](../../images/guides/edge-modules/fastly-edgemodules-first-use.png "Fastly Edge Modules Main Screen")

Click on Manage. Then Refresh button. A screen like this will be presented

![Fastly Edge Modules Selection Screen](../../images/guides/edge-modules/fastly-edge-modules-list-of-modules.png "Fastly Edge Modules Selection Screen")

You can now pick which modules you want to select. For example in this example I picked the CORS headers edge module

![Fastly Edge Modules Main Screen with one enabled module](../../images/guides/edge-modules/fastly-edgemodules-onemodule.png "Fastly Edge Modules Main Screen with one enabled module")

Now that you have selected which modules will be used you will need to go module by module and
configure them individually. Make sure you click Upload once you done configuring individual modules.

Currently following Edge Modules are bundled in

- [Increase timeouts for long running jobs](https://github.com/fastly/fastly-magento2/blob/master/Documentation/Guides/Edge-Modules/EDGE-MODULE-INCREASE-TIMEOUTS-LONG-JOBS.md) - Tweak timeouts for jobs/URLs that take longer than 1 minute
- [CORS headers](https://github.com/fastly/fastly-magento2/blob/master/Documentation/Guides/Edge-Modules/EDGE-MODULE-CORS-HEADERS.md) - Set CORS headers sent to the end user
- [Magento Cloud Sitemap rewrites](https://github.com/fastly/fastly-magento2/blob/master/Documentation/Guides/Edge-Modules/EDGE-MODULE-CLOUD-SITEMAP-REWRITE.md) - rewrite incoming /sitemap.xml URL to
a filesystem URL
- [Redirect one domain to another](https://github.com/fastly/fastly-magento2/blob/master/Documentation/Guides/Edge-Modules/EDGE-MODULE-REDIRECT-DOMAIN.md) - redirect one domain to another e.g. domain.com 
to www.domain.com. Useful for redirecting apex/naked domains to www.
