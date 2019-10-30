# Fastly Edge Modules - Mobile Themes support

You should use this module in case you use mobile themes with your Magento setup. By default Fastly does not
take into account device type and for caching purposes will store only a single version of a page. This module
adds detection of mobile devices currently in these three categories

* iPhone and iPod devices
* Android Mobile devices
* Tizen Mobile devices

It does so by inspecting the User-Agent header from the browser and broadly classifying them into two categories.

1. Desktop (default)
1. Mobile

For these two categories it will keep two distinct versions of a page.

It is possible to add additional categories and additional matching by adding new snippets e.g. you can add VCL
snippet to put bots and crawlers in their own pool e.g.

```vcl
if ( req.http.User-Agent ~ "(?i)(ads|google|bing|msn|yandex|baidu|ro|career|seznam|)bot" ) {
  set req.http.X-UA-Device = "bot";
}
```

Before you can use Fastly Edge Modules you need to [make sure they are enabled](https://github.com/fastly/fastly-magento2/blob/master/Documentation/Guides/Edge-Modules/EDGE-MODULES.md) and that you have selected the Mobile Theme support.

## Configurable options

There are no configurable options.

## Enabling

Click Upload as that will upload require VCL code to Fastly.

## Technical details

Following VCL will be uploaded

Snippet Type: vcl_recv

```vcl
# Mobile device detection for mobile themes
  set req.http.X-UA-Device = "desktop";

  if (req.http.User-Agent ~ "(?i)ip(hone|od)") {
      set req.http.X-UA-Device = "mobile";
  } elsif (req.http.User-Agent ~ "(?i)android.*(mobile|mini)") {
      set req.http.X-UA-Device = "mobile";
  } elsif (req.http.User-Agent ~ "(?i)tizen.*mobile") {
      set req.http.X-UA-Device = "mobile";
  }
```

Snippet Type: vcl_fetch

```vcl
# Add X-UA-Device Vary for HTML
if ( beresp.http.Content-Type ~ "text/html" ) {
  set beresp.http.Vary:X-UA-Device = "";
}
```

Snippet Type: vcl_deliver

```vcl
# Remove X-UA-Device Vary shown to the user
if ( fastly.ff.visits_this_service == 0 && !req.http.Fastly-Debug ) {
  unset resp.http.Vary:X-UA-Device;
}
```
