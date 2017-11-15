# Magento upgrade guide

This guide will instruct you on TODO list when doing upgrade to certain Magento version.

Upgrading to Magento 2.2 (or above):

Due to backward incompatible changes in configuration saving format for Magento versions 2.2 and above, you're required to run:

```
bin/magento fastly:format:serializetojson"
```

This will convert Fastly configuration data to JSON format supported in Magento 2.2 and above versions.

In case you need to revert changes, run:
```
bin/magento fastly:format:jsontoserialize"
```