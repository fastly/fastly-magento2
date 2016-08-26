# TLS Deployment

This document describes the options for implementing TLS. Fastly's TLS options
are detailed in their [documentation](https://docs.fastly.com/guides/securing-communications/ordering-a-paid-tls-option)

## Contents

- [Deployment Methods](#deployment-methods)
- [Terminating TLS at the Edge](#tls-terminated-at-fastly)
- [Encrypting from CDN to origin](#end-to-end-tls)
- [Using TLS for all requests](#using-tls-for-all-requests)

## Deployment Methods

There are a few ways each with their own benefits and downsides for deploying
TLS with Fastly and Magento. Indeed Magento offers a number configuration
options itself. These are set either in the admin interface or, using the
configuration options at install time:

```
--use-secure
--base-url-secure
--use-secure-admin
```

The most common deployment options are:

- [TLS terminated at Fastly](#tls-terminated-at-fastly). All communication from
  Fastly to Magento is in the clear.
- [End to End TLS](#end-to-end-tls). Communication is encrypted from the
  client to Fastly and from Fastly to Magento.

## TLS Terminated at Fastly

This configuration offloads TLS termination, a computationally expensive task
to Fastly. Communications are encrypted between the user and the CDN (Fastly).
The advantage is less work on the Magento server and so potential to run a
smaller machine. The downside is it is slightly less secure than encryption all
the way as traffic from the CDN to the Magento server is not encrypted.

To terminate TLS at Fastly you will need to choose one of the TLS options that
Fastly provides. For testing it is possible to use
[Fastly's Free Shared Domain](https://docs.fastly.com/guides/securing-communications/setting-up-free-tls)
option. If doing so, make sure to set the base-urls in Magento to the same name.

In Fastly's Configure page set the origin port to 80 (or the listening port of
the Magento server). In the TLS options for the origin make sure that 'Connect
to backend using TLS' is set to 'No'. This tells Fastly to send requests to the
origin unencrypted.

When using the VCL in the Fastly module for Magento 2, it sets a header 'Https'
to a value of 'on' if a request was received over TLS. The web server used to
deliver Magento will need to set an environment variable for Magento to
determine if a request was secured. The enviroment variable is also called
'HTTPS' and needs to be set to 'on'. The following snippet shows how this can be
achieved in Apache in a vhost:

```
SetEnvIf HTTPS "on" HTTPS="on"
```

## End to End TLS

This configuration uses TLS connections between the user and the CDN (Fastly)
and separately between Fastly and the Magento server. The advantage of this is
that communications are secure all the time they are in transit. The downside
is that the Magento server has to process TLS computation still.

The instructions in this section assume that only some of the URLs that are in
use will be for TLS.

To set this up, Fastly will need to be set to terminate TLS using one of the
options described in their documentation. The server will need to have either a
signed certificate or a self-signed certificate can be used. Make sure the
Magento server is properly configured for TLS.

Once the Magento server is properly configured, log in to the Fastly
application and configure a second origin with the same address. Make sure to
choose port 443, or set the TLS option to use TLS for this connection. Further
details of how to configure the options are in
[Fastly's documentation](https://docs.fastly.com/guides/securing-communications/connecting-to-origins-over-tls).

Add a condition to the server with the following details:

- name: Use secure server
- priority: 10 (default)
- condition:

```
req.http.Fastly-SSL
```

This will make sure that Fastly uses the secure origin for requests that came
in over TLS and the non-TLS for insecure requests.

## Using TLS for All Requests

This configuration uses only TLS for all requests between the client and CDN
(Fastly). The benefits of this are that all communications between client and
server are encrypted. If a request lands at the CDN and is not encrypted it
will be redirected to the secure URL. This helps avoid mixed content on pages.

To set this up, configure the URLs in Magento to be secure. This can be done on
the command line by:

```
/usr/bin/php bin/magento setup:store-config:set \
    --base-url='https://example.com/' \
    --use-secure='1' \
    --use-secure-admin='1'
```

Make sure that the webserver shows that a request has had TLS offloaded by
setting the environment variable 'Https' to 'On' as described above.

As a failsafe for any URLs which may not have the correct protocol prefixed to
them add a rule to 'Force TLS' in Fastly. The rule is described in
[Fastly's Documentation](https://docs.fastly.com/guides/securing-communications/allowing-only-tls-connections-to-your-site).