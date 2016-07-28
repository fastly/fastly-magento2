# FASTLY CDN FOR MAGENTO2 DOCUMENTATION

Thank you for using the "Fastly CDN module for Magento2" (Fastly_Cdn).

This package contains everything you need to connect fastly.com (Fastly) with
your Magento commerce shop and to get the most out of Fastly's powerful caching
capabilities for a blazing fast eCommerce site. The Fastly_Cdn module consists
of two main components:

- The Magento2 module and
- [the bundled Varnish Cache configuration file](https://github.com/fastly/fastly-magento2/blob/master/etc/fastly.vcl)
  (VCL).

The Fastly_Cdn module relies on Magento2's page cache functionality and extends
 its Varnish capabilities to leverage Fastly's enhanced caching technology and
 GeoIP support.

The second component, the VCL, configures Fastly's Varnish to process the client
 requests and Magento's HTML response according to the Cache-Control headers the
 Fastly_Cdn module adds to every response.

## 1. Prerequisites

Before installing the Fastly_Cdn module you should setup a
test environment as you will need to put Fastly in front which will certainly
take a while for configuring and testing. If you directly rollout this solution
to your production server you might experience issues that could affect your
normal eBusiness.

Ensure that your Magento2 store is running without any
problems in your environment as debugging Magento2 issues with Fastly in front
might be difficult.

Fastly_Cdn supports Magento2 Community and Enterprise Edition from version 2.0
onwards.

You need an account with [fastly.com](https://www.fastly.com/signup) which allows
[uploading of custom VCL](https://docs.fastly.com/guides/vcl/uploading-custom-vcl).
If you need professional services for setup your environment please contact
fastly.com.

## 2. Installation

This chapter describes the installation and configuration of the Magento2 module
 as well as the settings within your fastly.com account.

### Magento module

The installation of the Magento module is pretty easy and can be performed in
 a few ways.

#### Installing from the Magento Marketplace using Web Setup Wizard

This will require an account with Magento Commerce and the api keys will be
used to sync with the marketplace.

1. Open a browser to the [Magento Marketplace](https://marketplace.magento.com/fastly-magento2.html)
    and add the module to the cart. Check out and ensure that this is added to
    your account.
1. Log into the admin section of the Magento system in which to install the
    module as an administrator.
1. Start the Web Setup Wizard by navigating to 'System > Web Setup Wizard'.
1. Click 'Component Manager' to synchronise with Magento Marketplace.
1. Click to 'Enable' the Fastly_Cdn module. This will start the wizard.
1. Follow the on screen instructions ensuring to create backups.
1. Proceed to [Configuring the Module](#configure-the-module).

#### Installing using Composer

1. Login (or switch user) as the Magento filesystem owner.
1. Ensure that the files in 'app/etc' under the Magento root are write enabled
    by the Magento Filesystem owner.
1. Ensure that Git and Composer are installed.
1. Inside the Magento Home directory add the composer repository for the module.

    ```
    composer config repositories.fastly-magento2 git "https://github.com/fastly/fastly-magento2.git"
    ```

1. Next fetch the module with:

    ```
    composer require fastly/magento2
    ```

1. Once the module fetch has completed enable it by:

    ```
    bin/magento module:enable Fastly_CDN
    ```

1. Then finally the clean up tasks:

    ```
    bin/magento setup:upgrade
    ```

    followed by:

    ```
    bin/magento cache:clean
    ```

1. Once this has completed log in to the Magento Admin panel and proceed to
    [Configuring the Module](#configure-the-module).

#### Installing from zip file

1. Open a browser to [Github](https://github.com/fastly/fastly-magento2/releases)
    note/copy the URL of the version to install.
1. Log in to the Magento server as the Magento filesystem owner and navigate to
    the Magento Home directory.
1. Create a directory `<magento home>/app/code/Fastly/Cdn/` and change directory
    to the new directory.
1. Download the zip/tarball and decompress it.
1. Move the files out of the `fastly-magento2` into
    `<magento home>/app/code/Fastly/Cdn/`.
1. It is possible at this point to install with either the Web Setup Wizard's
    Component Manager, or on the command line.
1. To install in the Web Setup Wizard. Open a browser and log in to the Magento
    admin section with administrative privileges.
1. Navigate to 'System > Web Setup Wizard'.
1. Click 'Component Manager' scroll down and locate 'Fastly_Cdn'. Click enable
    on the actions.
1. Follow the on screen instructions ensuring to create backups.
1. Proceed to [Configuring the Module](#configure-the-module).
1. To enable the module on the command line change directory to the Magento
    Home directory. Ensure you are logged in as the Magento filesystem owner.
1. Verify that 'Fastly_Cdn' is listed and shows as disabled: `bin/magento
    module:status`.
1. Enable the module with: `bin/magento module:enable Fastly_Cdn`.
1. Then we need to ensure the configuration tasks are run: `bin/magento
    setup:upgrade`.
1. Finally on the command line to clear Magento's cache run: `bin/magento
    cache:clean`.
1. Once this has been completed log in to the Magento Admin panel and proceed
    to [Configuring the Module](#configure-the-module).

Go to Stores > Configuration. Then to System > Advanced. Expand the section
'Full Page Cache'. From the 'Caching Application' select 'Fastly CDN'. You can
then add the credentials and choose the caching options.

If any critical issue occurs you can't easily solve, call
`bin/magento module:disable Fastly_Cdn`
to disable the Fastly_Cdn module. If necessary clear Magento's cache again.

### Fastly App

The Fastly Magento plugin requires the ability to upload custom VCL to your
services. If you don't already have it, send support@fastly.com a request
asking to have the VCL uploading enabled on your account.

Proceed with the configuration.

## 3. Configuration

This section handles the different configuration options
for the module as well as settings that have to be configured on the server.

### 3.1 General Settings

In the following section the configuration options for the Fastly CDN module
are explained. Some of them can be changed on the website and store view level
which allows fine grained configurations for different store frontends.

In your Magento2 backend go to
'Stores -> Configuration -> System', in the "Advanced" section and open
"Full Page Cache" tab.

**Note** that if you change a value here Fastly
will not reflect it until you purge its HTML objects or the TTL of the cached
objects expires.

#### Caching Application

Make sure you choose "Fastly CDN" if you want to use Fastly service. This
enables the basic functionality like setting HTTP headers and allows cache
cleaning on the Magento Cache Management page. This option should be set to
"Fastly CDN" as soon as you point one of your store domains to the Fastly
service even if you would like to deactivate the caching.
Until this is done caching behavior may not work as expected and cleaning
options on the Cache Magement page won't be available.

#### TTL for public content

Fastly delivers cached objects without requesting the web server or Magento
again for a certain period of time defined in the TTL (time to live) value. You
can adjust the TTL for your shop pages.

Note that this field only allows numeric values in seconds. It doesn't support
the same notation that can be used in the VCL. "2h" (2 hours) have to be
entered as "7200" seconds. For static contents Fastly can use Cache-Control
headers which might be set by the web server (e.g. see .htaccess for
mod_expires settings).

#### Fastly Service ID

Enter the service id of the Fastly service that is connected to the current
scope.

#### Fastly API key

Enter your Fastly API key.

(Details of how to find these are documented [here](https://docs.fastly.com/guides/account-management-and-security/finding-and-managing-your-account-info)).

#### Stale content delivery time

Fastly can serve stale content even if the TTL has expired. During the time it
takes Fastly to fetch the fresh content from Magento it can serve stale content
from its cache. This setting defines the time in seconds to allow Fastly to
serve stale content after the "normal" TTL has expired.

#### Stale content delivery time in case of backend error

This setting defines the time in seconds that stale content can be delivered in
case the backend is down or cannot respond properly.

#### Purge category

This option binds automatic purge of category (Fastly) cache with is update
event. If you always want up-to-date category information on  front-end set the
option value to "Yes" and category cache will be invalidated each time a
category update occurs.

#### Purge product

This option binds purge of product (Fastly) cache with product and product's
stock update. If set to "Yes" product pages cache is invalidated each time
product update or product's stock update occurs.

Additionally, if "Purge Category" option is set to "Yes" this triggers
product's categories cache purge on product/product stock update. This option
is useful to keep product pages and categories up-to-date when product becomes
out of stock (i.e. when the last item purchased by a customer).

#### Purge CMS page

This option binds automatic purge of CMS page (Fastly) cache with its update
event. If set to "Yes" CMS page cache is invalidated each time CMS page update
event occurs (i.e. CMS page content update via Magento admin).

#### Use Soft Purge

Using soft purge will not remove the content immediately from Fastly's cache
but mark it as stale. In combination with the stale timings your customers will
be serverd stale content very fast while Fastly is updating the content in the
background.

#### 3.2 GeoIP handling

GeoIP handling will get the country of a web client
based on it's IP address. This feature can be used to either automatically
redirect visitors to the store matching their country or to show them a dialog
to select the desired store themselves.

##### Configuration

In your Magento2 backend go to Stores -> Configuration -> System in the
"Advanced" section and open "Full Page Cache" tab and choose "Yes" with "Enable
GeoIP". Make sure to be on store configuration scope.

#### General behavior

The Fastly module supports two options to serve the store view based on GeoIP
country code: It can show the visitor a modal dialog to give him the option to
switch to a frontend that fits to his country or he can be redirect
automatically. Both actions are performed using JavaScript and can be adjusted
if necessary.

In the VCL the cookie headers are examined. If either the X-Magento-Vary or
form_key cookie are present this Fastly will not take any action as it presumes
the customer either saw (and maybe interacted with) the modal dialog or was
automatically redirected to the matching store. If the form_key cookies is
present the visitor has been browsing around so he shouldn't be disturbed by
dialogs or redirects.

##### GeoIP Action

Choose "Dialog" to show a modal dialog to the visitor. This gives him the
option to switch to the suggested store based in the GeoIP lookup and the
"GeoIP Country Mapping" (see below) or stay on the current frontend. Choose
"Redirect" to perform redirect the visitor to an appropriate store view.

##### GeoIP Country Mapping

For every country you want to map to a specific store you have to enter a
country code and select a target store. All country codes use ISO
3166-1-alpha-2 codes. You can use "*" as wildcard to match all country codes.

##### Prevent redirect or static blocks to be shown

You probably don't want to redirect your customers to another store if the
country of your visitor matches the current store. To prevent redirects you
have to add a mapping using the country code of that store and leave the other
field empty. This way, if your customer is in the "right" store (based on the
country), no GeoIP based action will be triggered.

## 4. Cache cleaning, PURGE requests

Fastly caches objects for a certain period of time according to their TTL.
After that the object will not be requested from the web server or Magento
again. Until the TTL expires Fastly will deliver the cached object no matter
what will change within Magento or the webserver's file system. To force Fastly
to cleanup its' cache and to retrieve the information again from the backend
you can trigger a purge request right from Magento.

In the Magento2 backend go to System -> Cache Management. If you have enabled
the Fastly_Cdn module in the configuration you will see three new buttons
"Additional Cache Management" section.

### Purge by content type

You can purge objects in Fastly's Cache based on its content type by just
clicking "Clean Fastly CDN Cache by content type". This will allow you for
example to remove the CSS files of all store views in Fastly if they have been
modified without the need to invalidate any other object which will save a lot
of resources on high frequented stores.

### Purge by store

You can purge objects in Fastly's Cache based on the store by just clicking
"Clean Fastly CDN Cache by store". This will only remove content that is
generated from Magento. Images, CSS files or JavaScripts will not be purged.

### Purge a URI

It is also possible to purge a single url (e.g. page) using "Quick Purge".
Enter desired URL in input field next to "Quick Purge" button and press it. If
URL is valid you'll see a success message for purged page.

### Purge all

Beside these direct purge requests Fastly_Cdn has observers for "Flush Magento
Cache" and "Flush Cache Storage" to purge all objects in Fastly together with
the Magento cache refresh. It also has observers for "Flush Catalog Images
Cache" and "Flush JavaScript/CSS Cache" to clean objects that match the
appropriate surrogate keys in Fastly. All HTML objects will be purged too as
the product image and JavaScript/CSS paths will change when Magento generated
them again so the cached HTML objects might contain wrong paths if not
refreshed. You can also enable automatic purging of CMS pages, categories and
products when they are saved (see configuration). If you don't want these
observers to take automatic action comment them out in the config.xml of the
Fastly_Cdn module.

## 5. VCL Design Exceptions

By default Varnish does not take into account
User-Agent string of a request when building its cache object. Magento Design
Exceptions use regular expressions to match different design configurations to
User-Agent strings. In order to make Design Exceptions work with Varnish you
will have to renew Varnish VCL each time Design Exceptions are updated.
To do this use the button "Export VCL for Varnish fastly" button in the
configuration section and upload the new VCL to the Fastly service.
