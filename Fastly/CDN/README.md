**FASTLY CDN FOR MAGENTO DOCUMENTATION**

Thank you for using the "Fastly CDN module for Magento" (FastlyCDN).

This package contains everything you need to connect fastly.com (Fastly) with
your Magento commerce shop and to get the most out of Fastly's powerful caching
capabilities for a blazing fast eCommerce site. The FastlyCDN module has been
architectural certified by Varnish Software to ensure highest quality and
reliability for Magento stores. The FastlyCDN module consists of two main
components:

- The Magento module and
- the bundled Varnish Cache configuration file (VCL).

The FastlyCDN module basically sets the correct Cache-Control headers according to the
configuration and the visitor session and provides an interface for purginf Fastly's
cache.

The second component, the VCL, configures Varnish to process the client requests and
Magento's HTML response according to the Cache-Control headers the FastlyCDN module adds
to every response.


# 1. Prerequisites

Before installing the FastlyCDN module you should setup a
test environment as you will need to put Fastly in front which will certainly
take a while for configuring and testing. If you directly rollout this solution
to your production server you might expirience issues that could affect your
normal eBusiness.

Ensure that your Magento Commerce shop is running without any
problems in your environment as debugging Magento issues with a Fastly in front
might be difficult.

FastlyCDN supports Magento Community Edition from version
1.7 and Magento Enterprise Edition from version 1.12 onwards.

You need an account with fastly.com which alows uploading of custom VCL. If you need
professional service for setup your environment please contact fastly.com.  


# 2. Installation


This chapter describes the installation of the Magento module
as well as the settings within your fastly.com account.

## Magento module

The installation of the Magento module is pretty easy:

1. Copy the contents of the app directory in the archive to the app directory of your
   Magento instance.
2. Go to the Magento backend and open the Cache Management (System -> Cache
   Management) and refresh the configuration and layout cache.
3. Due to Magento's permission system, please log out and in again before continuing with
   the next step.
   
If any critical issue occurs you can't easily solve, go to app/etc/modules, open
"Fastly_CDN.xml" and set "false" in the "active" tag to deactivate the FastlyCDN module.
If necessary clear Magento's cache again.

Upload the VCL file bundled with the FastlyCDN module to your Fastly service.


## Fastly Website

Tbd. Ask Fastly support for assistance configuring your Fastly account.

Proceed with the configuration.


# 3. Configuration

This section handles the different configuration option
for the module as well as settings that have to be done on the server side.

Important: If you are using Magento Enterprise make sure to deactive the Page
Cache before enabling FastlyCDN module. You can do this in System > Cache
Management.


## 3.1 General Settings

In your Magento backend go to System -> Configuration -> "Fastly CDN" in the "Services"
section and open "General Settings" tab.

In the following section the configuration options for the FastlyCDN module are explained.
Most of them can be changed on website and store view level which allows fine granulated
configurations for different store frontends. Note that if you change a value here Fastly
will not reflect it until you purge its HTML objects or the TTL of the cached objects
expires.

### Enable cache module
This enables the basic functionality like setting HTTP headers and
allows cache cleaning on the Magento Cache Management page. This option should
be set to "Yes" globally as soon as you point one of your store domains to the Fastly
service even if you like to deactivate the caching for certain websites or store views
(see option below). Otherwise it may result to unexpected caching behavior and cleaning
options on the Cache Magement page won't be available.

### Fastly Service ID
Enter the service id of the Fastly service that is connected to the current scope.

### Fastly API key
Enter your Fastly API key.

### Disable caching
This option allows you to deactivate caching of every Magento frontend page in Fastly.
This is useful for development or tests by passing all requests through Fastly without
caching them. If you have a staging website within your Magento Enterprise instance make sure this option is set to
"Yes" for this website. Technically Fastly will still be active but every
request gets a cache control of "private".

### Disable caching for routes
Certain controllers or actions within Magento must not be cached by Fastly as their
response surely contains custom data or it is necessary to process a request in
database (API calls, payment callbacks). Although Fastly passes all POST
requests (which most often are used to submit forms with custom information
etc.) you can define the controllers and actions that should have the "no-cache"
flag in their HTTP response header.

Note: The function relies on  Mage_Core_Controller_Varien_Action::getFullActionName().

### Default cache TTL
Fastly delivers cached objects without requesting the web server or Magento
again for a certain period of time defined in the TTL (time to live) value. You
can adjust the TTL for your shop pages on store view level which allows you to
have different TTLs for your frontend pages.

Note that this field only allows numeric values in seconds. It doesn't support the same
notation that can be used in the VCL. "2h" (2 hours) have to be entered as "7200" seconds.
For static contents Fastly uses the default TTL value defined in the  vcl_fetch section of
the VCL (Default: set beresp.ttl = 3600s).

### Stale content delivery time
Fastly can serve stale content even if the TTL has expired. During the time it takes
Fastly to fetch the fresh content from Magento it can serve stale content from
ist cache. This setting defines the time in seconds to allow Fastly to serve
stale content after the "normal" TTL has expired.

### Stale content delivery time in case of backend error
This setting defines the time in seconds that stale content can be delivered in case the
backend is down or cannot respond properly.

### Cache TTL for routes
This options allows you to adjust Fastly cache TTL on a per
magento  controllers/actions basis. To add a new TTL value for route

1. click "Add route" button
2. input route (e.g. "CMS", "catalog_product_view");
3. input TTL for route in seconds (e.g. "7200").

"Default Cache TTL" value is used when no TTL for a given route is defined.

### Purge category
This option binds automatic purge of category (Fastly) cache with is update event. If you
always want up-to-date category information on  front-end set the option value
to "Yes" and category cache will be invalidated each time a category update occurs.

### Purge product
This option binds purge of product (Fastly) cache with product and  product's stock
update. If set to "Yes" product pages cache is 
invalidated each time product update or product's stock update occurs.

Additionally, if "Purge Category" option is set to "Yes" this triggers 
product's categories cache purge on product/product stock update. This option is
useful to keep product pages and categories up-to-date when product becomes out
of stock (i.e. when the last item purchased by a customer).

### Purge CMS page
This option binds automatic purge of CMS page (Fastly) cache with its update event.
If set to "Yes" CMS page cache is invalidated each time CMS page update event
occurs (i.e. CMS page content update via Magento admin).

### Use Soft Purge
Using soft purge will not remove the content immediately from Fastly's cache but mark
it as stale. In combination with the stale timings your customers will be serverd stale
content very fast while Fastly is updating the content in the background.

### Debug
This option lets some X headers pass. Use it only for development or debugging. This
should be set to "No" on production systems.


## 3.2 ESI

Edge Side Includes (ESI) is implemented in Varnish as a subset of the W3C definition
(http://www.w3.org/TR/esi-lang) and supports esi:include and esi:remove only.

With ESI enabled your Magento installation will become even faster than running only on
Fastly alone. ESI is used to cache recurring snippets (aka blocks in Magento) and reuse
them in different pages.

### 3.2.1 Form Key Handling

As with version CE 1.8 and EE 1.13 Magento introduced form keys in
the frontend. Form keys are handled automatically with the module by using ESI
blocks.

### 3.2.2 ESI blocks

FastlyCDN comes preconfigured with ESI handling for these
blocks:
- top links
- welcome message
- sidebar/mini cart
- recently viewed products
- recently compared products
- wishlist

To enable ESI functionality go to System -> Configuration -> "Fastly CDN" in the
"Services" section and open "ESI Settings" tab.

**MAGENTO ENTERPRISE USERS NEED TO MAKE
SURE THAT THE "PAGE CACHE" IN SYSTEM -> CACHE MANAGEMENT IS DISABLED BEFORE
ENABLING ESI.**

### 3.2.3 ESI configuration

#### Default ESI TTL
The default TTL for ESI blocks in seconds. This applies for blocks that have no specific
value in the next section.

#### ESI TTL for blocks
You can override the default TTL by setting block specific values. The
block name must match the tag names defined in the config.xml in 
app/code/community/fastly/CDN/etc in the <FastlyCDN_esi_tags> section.

#### ESI Strict Rendering
When set to "No" block logic gets simplified to get better hit rates. However the content
might slightly differ from native Magento block. This affects especially to the "recently
viewed products" and "recently compared products" block which natively filters the list
of viewed or compared products.

#### ESI Debug
Enables markers to identify ESI blocks in the frontend. It will also
echo the internal ESI URL per block for debugging.

Use this only in a test environment.

### 3.3 GeoIP handling

GeoIP handling will get the country of a web client
based on it's IP address. This feature can be used to either automatically
redirect customers to the store matching their country or to show them a dialog
to select the desired store themselves.

#### Configuration
To enable GeoIP handling go to System -> Configuration -> "FastlyCDN" and
open the "GeoIP Settings" tab and choose "Yes" with "Enable GeoIP". Make sure to
be on store configuration scope.

#### General behavior
The module will set a cookie with the name "FASTLY_CDN_GEOIP_PROCESSED " after a redirect
or when a dialog is displayed.

If this cookie is set on the customer side this module will take no action at all as we
presume the customer either saw (and maybe interacted with) the popup or was automatically
redirected to the matching store.

#### Show a dialog to select target store
When set to "yes" your customers will be presented with a dialog.
When set to "no" your customers will be redirected.
The action performed depends on two variables:
1. the current store
2. the country of the visitor

To configure the dialog or the redirect url you have to switch to store scope and add
mappings for the countries you want to redirect/inform.
All country mappings use ISO 3166-1-alpha-2 codes.

#### Mapping for static CMS blocks
For every country you want to show a dialog you have to enter a country code an
select a static CMS block to display. This module comes with predefined CMS
blocks to be a base for your custom development. The modal window itself can be
customized by editing the template file at app/design/frontend/base/default/template/
FastlyCDN/geoip/dialog.phtml

The name of the CMS blocks bundled with the module start with
"Fastly CDN GeoIP dialog in ...". There is a version in English and a version in German.
To make the dialogs work you have to replace the value "EN" (or "DE") within the options
of the select tag with the id "geoip-select" with the URI you want your customers to be
redirected to.

#### Mapping for redirects
For every country you want to redirect to a specific store
you have to enter a country code an select a target store.

You can define either redirect or show dialog.

#### Prevent redirect or static blocks to be shown
You probably don't want to redirect your customers to another store if the country of
your visitor matches the current store. To prevent redirects you have to add a mapping
using the country code of that store and leave the other field empty. This way, if your
customer is in the "right" store (based on the country), no GeoIP based action
will be triggered.


# 4. Cache cleaning, PURGE requests
Fastly caches objects for a certain period of time according to their TTL. After that the
object will not be requested from the web server or Magento again. Until the TTL expires
Fastly will deliver the cached object no matter what will change within Magento or the
webserver's file system. To force Fastly to cleanup its' cache and to retrieve the
information again from the backend you can trigger a purge requests right from Magento.

In the Magento backend go to System -> Cache Management. If you have enabled the
FastlyCDN module in the configuration you will see a new button "Clean Fastly
CDN Cache" in the "Additional Cache Management" section.

## Purge by content type

You can purge objects in Fastly's Cache based on its content type by just
clicking "Clean Fastly CDN Cache by content type". This will allow you for
example to remove the CSS files of all store views in Fastly if they have been
modified without the need to invalidate any other object which will save a lot
of resources on high frequented stores.

## Purge by store

You can purge objects in Fastly's Cache based on the store by just clicking "Clean Fastly
CDN Cache by store". This will only remove content that is generated from Magento. Images,
CSS files or JavaScripts will not be purged.

## Purge a URI

It is also possible to purge a single url (e.g. page) using "Quick Purge". Enter desired
URL in input field next to "Quick Purge" button and press it. If URL is valid you'll see a
success message for purged page.

## Purge all

Beside these direct purge requests FastlyCDN has observers for "Flush Magento Cache" and
"Flush Cache Storage" to purge all objects in Fastly together with the Magento cache
refresh. It also has observers for "Flush Catalog Images Cache" and "Flush JavaScript/CSS
Cache" to clean objects that match the appropriate surrogate keys in Fastly. All HTML
objects will be purged too as the product image and JavaScript/CSS paths will change when
Magento generated them again so the cached HTML objects might contain wrong paths if not
refreshed. You can also enable automatic purging of CMS pages, categories and products
when they are saved (see configuration). If you don't want these observers to take
automatic action comment them out in the config.xml of the FastlyCDN module.


# 5. VCL Design Exceptions

By default Varnish does not take into account
User-Agent string of a request when building its cache object. Magento Design
Exceptions use regular expressions to match different design configurations to
User-Agent strings. In order to make Design Exceptions work with Varnish you
will have to renew Varnish VCL each time Design Exceptions are updated. Here's
what you have to do: tbd


# 6. Troubleshooting

This section handles know issues with the module as well as best practices when using
FastlyCDN.

## 6.1 Known issues

- "Use SID in Frontend" in System Configuration -> Web -> Session Validation Settings
  must not be set to "yes" otherwise a GET parameter ___SID will be added which disables
  caching at all.
- "Redirect to CMS-page if Cookies are Disabled" in System Configuration->Web-> Browser
  Capabilities Detection must be turned off as visitors served by Fastly won't get a
  cookie until they put something in the cart of login.
- Logging and statistics will be fragmentary (Fastly won't pass cached requests to the
  webserver or Magento). Instead make use of a JavaScript based statistics like Google
  Analytics or use Fastly's metrics.
- Running FPC (Magento Enterprise Full Page Cache) along with ESI will result in ESI not
  displaying any cached blocks. To use FastlyCDN with Magento Enterprise you have to
  disable FPC completely.

## 6.2 Prevent caching for custom modules

In your Magento installation you will surely have custom
modules whose HTML output shouldn't be cached. Therefore you have to add their
controllers to the "Disable caching for routes" configuration to prevent caching
of their output.

## 6.3 Prevent caching for HTML/PHP files outside Magento

Fastly as a CDN respects caching information from the backend server like
"Cache-Control: max-age=600" or "Expires: Thu, 19 Nov 2021 08:52:00 GMT".
FastlyCDN uses "Expires" to tell Fastly whether a Magento page is cacheable and
how long.

If you have mod_expires installed in your Apache and the Magento
default setting in your .htaccess 'ExpiresDefault "access plus 1 year"' this
will allow Fastly to cache every object outside of Magento (e.g. files in js,
media or skin folder) for one year. However this also affects HTML or PHP files
if they don't set their own "Cache-Control" or "Expires" header. If you don't
want HTML contents which don't explicitly allow caching to be cached by Fastly,
add this line to the mod_expires section of your .htaccess file:

	ExpiresByType text/html A0

This will set the expiry time of the object equal to the delivery time which
will not allow Fastly to cache the object.

Note that if a "Expires" header is already set in the HTTP response header mod_expires
will respect it and pass this header without changes.

## 6.4 Vary HTTP header for User-Agent

Some administrators have this line in Magento's .htaccess file:

    Header append Vary User-Agent env=!dont-vary

However this forces Fastly's Cache to have one cache element per user agent
string for each URL which makes caching almost useless. If you have the feeling
that in one browser your cache is hot while in a different browser Fastly has no
cache hits check your backend response and make sure the Vary header only looks
like this:

	Vary: Accept-Encoding

