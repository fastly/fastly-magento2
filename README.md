# FASTLY CDN FOR MAGENTO2 DOCUMENTATION

Thank you for using the "Fastly CDN module for Magento2" (Fastly_Cdn).

This package contains everything you need to connect fastly.com (Fastly) with
your Magento commerce shop and to get the most out of Fastly's powerful caching
capabilities for a blazing fast eCommerce site.

## Contents

- [Description](#description)
- [Support](#support)
- [Features](#features)
- [Contributing](#contributing)

## Description

The Fastly_Cdn module consists of two main components:

- The Magento2 module and
- [the bundled Varnish Cache configuration file](etc/fastly.vcl)
  (VCL).

The Fastly_Cdn module relies on Magento2's page cache functionality and extends
its Varnish capabilities to leverage Fastly's enhanced caching technology and
GeoIP support.

The second component, the VCL, configures Fastly's Varnish to process the
client requests and Magento's HTML responses according to the Cache-Control
headers the Fastly_Cdn module adds to every response.

## Support

Documentation for this module can be found in the
[Documentation](Documentation/)
folder.

Help using this module can be found by posting to
[Fastly's community forum](https://community.fastly.com/).

For Fastly customers with a [Support Package](https://www.fastly.com/support)
please reach out via the normal channels.

If there are issues/errors with integrating the module, please post
[details](Documentation/OPENING-ISSUES.md) in the github repository issues.

## Features

The module utilises a number of features of Fastly's services. This section
will provide a brief overview of the ones available in the Fastly_Cdn module.

**Geo IP Detection:** Using the client's IP this allows a regional store to be
delivered to the user.

**Serving Stale on Errors:** This allows an expired copy of content to be used
in case of errors on the origin. This prevents site outages being visible to users.

**Serving Stale while Revalidating:** This allows an expired copy of content to
be served while the content is refreshed from origin. This maintains
performance while keeping a fresh cache.

**Soft Purging:** This marks content as expired (before the TTL). Using this
means that content can be freshened actively while using stale content to users
for a fast site.

**N.B.** More in-depth explanations of these features can be found in
[Fastly's Documentation](https://docs.fastly.com/).

## Contributing

We welcome pull requests for issues and new functionality. If you have some
contribution to make please take the following steps:

- Fork the repository from the master branch.
- Create a new branch for your features / fixes.
- Make the changes you wish to see.
- Add tests for all changes.
- Create a pull request with details of what changes have been made.
  Explanation of new behaviour. Link to issue that is addressed. Ensure
  documentation contains the correct information.
- Pull requests will be reviewed and hopefully merged into a release.
