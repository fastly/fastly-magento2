# Maintenance Support guide

This guide will show you how to setup Maintenance Support. This feature allows you to whitelist IP addresses on the 
Fastly service during maintenance and prevents non-whitelisted IPs from accessing cached content.

# Requirements

Before using this feature, make sure to Upload VCL to Fastly from the Fastly Configuration menu, or
by using the CLI command `bin/magento fastly:conf:set -u`. This will ensure that your Fastly service has the required 
snippets, dictionary and ACL containers.

# Toggle Super Users

To enable Super Users, use the Enable Super Users button in the Maintenance Support tab of the Fastly Configuration,
or use the CLI command `bin/magento fastly:superusers -e`. Enabling Super Users will block all traffic from IP addresses 
not located in the `maint_allowlist` ACL container. The IP addresses should be updated by using the Update Super User 
IPs option.

To disable Super Users, use the same button or the CLI command `bin/magento fastly:superusers -d`.

# Update Super User IPs

Before Super Users can be enabled, the list of allowed IP addresses in the `maint_allowlist` ACL container has to 
contain at least one IP address. To update the list, use the Update Super Users IPs button in the Maintenance
Support tab of the Fastly Configuration or the CLI command `bin/magento fastly:superusers -u`. This process will read 
all of the IP addresses contained in the `var/.maintenance.ip` file and add them to the `maint_allowlist` ACL container.


