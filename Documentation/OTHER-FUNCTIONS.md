# Other Fastly_Cdn Functions

## Contents

* [Purging](#purging)

## Purging

Fastly caches objects for a certain period of time up to their TTL.
After that the object will not be requested from the web server or Magento
again. Until the TTL expires Fastly will deliver the cached object no matter
what will change within Magento or the webserver's file system. To force Fastly
to cleanup its cache and to retrieve the information again from the backend
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

### Purge a URL

It is also possible to purge a single URL (e.g. page) using "Quick Purge".
Enter the desired URL in input field next to "Quick Purge" button and press
it. If URL is valid you'll see a success message for purged page.

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
