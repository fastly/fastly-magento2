# CLI Functions

## Contents

* [Configuration](#configuration)
* [Format conversion](#format)

## Configuration

Certain configuration options can be set using CLI:

- Service ID (--service-id)
- Token (--token)
- Uploading default VCL files (--upload-vcl)
- Activating last cloned version (--activate)
- Enabling Fastly (--enable)
- Disabling Fastly (will set default build-in cache as used caching mechanism) (--disable)
- Testing connection (--test-connection)
- Flushing configuration cache (--cache)

Notes: To upload VCL or activate version, service ID and token must be correct (you can check this with test option)

Full usage example for setup of fastly from command line:
php bin/magento fastly:conf:set --service-id xxxxxxxxxxx --token xxxxxxxxxxx --upload-vcl --activate --enable --test-connection --cache


## Format conversion

Magento 2.2 and above differs in format saving from prior versions as it uses JSON format instead of serialization from M2.2.
If by any chance you encounter issues with this, this functions will convert Fastly configuration data to required format.
Use this only if you encounter issues when opening Fastly options in admin configuration.

Serialization to JSON
```
bin/magento fastly:format:serializetojson"
```

JSON to Serialization (In case you need to revert changes, run):
```
bin/magento fastly:format:jsontoserialize"
```
