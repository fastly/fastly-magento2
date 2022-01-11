# Updating datacenters.json

The `datacenters.json` file is used by the code base to generate a shielding dropdown list in the backend configuration UI.  
- https://github.com/fastly/fastly-magento2/search?q=DataCenters

This file should be updated when POPs are added or retired (e.g., shielding migration).

```sh
# update "datacenters.json" with the latest data
$ cd etc/shielding
$ curl -sv https://api.fastly.com/datacenters -H 'fastly-key: your_personal_token' | jq . > datacenters.json
```

then review diff, and push the changes or submit a PR.
