# Run this script before new release to embed version tag in the right places so
# we can debug easier
VERSION=`cat VERSION`

sed -i "s/resp.http.Fastly-Magento-VCL-Uploaded = \".*\"/resp.http.Fastly-Magento-VCL-Uploaded = \"$VERSION\"/g" etc/vcl_snippets/*.vcl
sed -i "s/req.http.Fastly-Magento-VCL-Uploaded = \".*\"/req.http.Fastly-Magento-VCL-Uploaded = \"$VERSION\"/g" etc/vcl_snippets/*.vcl
sed -i "s/\"version\": \".*\"/\"version\": \"$VERSION\"/g" composer.json
sed -i "s/\"Fastly-Module-Enabled\", \".*\"/\"Fastly-Module-Enabled\", \"$VERSION\"/g" Model/Layout/LayoutPlugin.php
