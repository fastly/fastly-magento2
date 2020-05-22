# Fastly Edge Modules - DataDome integration 

This module will enable [DataDome integration](https://docs.datadome.co/docs/magento-with-fastly-cdn "DataDome Documetation"). Datadome is a real time bot protection. Start you 30 days free trial to enable module [here](https://datadome.co/free-signup/ "Free Trial Link")

This module provides integration between Fastly Edge module and Datadome. It's available from module version 1.2.127+. 

Before you can use Fastly Edge Modules you need to [make sure they are enabled](https://github.com/fastly/fastly-magento2/blob/master/Documentation/Guides/Edge-Modules/EDGE-MODULES.md) and that you have selected the Datadome integration module.

After you have enabled the module it's time to configure. You will be prompted with a screen like this

![Fastly Edge Module DataDome configuration](../../images/guides/edge-modules/edge-module-datadome.jpg "Fastly Edge Module DataDome configuration")

## Configurable options

| Setting | Description |
|---------|-------------|
| API Key | Your DataDome License key |
| Exclusion Regex | The regex that will be applied to req.url.ext |
| Connection Timeout | How long to wait for a timeout in milliseconds. |
| First byte timeout | How long to wait for the first byte in milliseconds. |
| Between bytes tiemout | How long to wait between bytes in milliseconds. |

## Enabling

After any change to the settings you need to click *Upload* as that will upload require VCL code to Fastly.

## Full documentation 

You can access the full documentation on [DataDome's website](https://docs.datadome.co/docs/magento-with-fastly-cdn, "Documentation")