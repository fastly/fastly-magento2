# Fastly_Cdn Release Notes

## 1.2.41

- Allow user to override default first byte timeout for admin paths https://github.com/fastly/fastly-magento2/pull/135

## 1.2.40

- Allow user to override default list of query arguments to strip out https://github.com/fastly/fastly-magento2/pull/134

## 1.2.39

- Use frontName from app/etc/env.php to generate VCL statements for handling /admin/ URLS https://github.com/fastly/fastly-magento2/pull/132
- Handle cases where more than 256 surrogate keys are being purged. Those need to be broken up into multiple transactions https://github.com/fastly/fastly-magento2/pull/133/files

## 1.2.38

- Fix for Edit Backends where due to improper escaping in certain situations backends would not show

## 1.2.37

- Add ability to see full stack trace of purge all requests. Often times 3rd party modules will invoke purge all
needlessly and this allows you to track down who is making the calls. By default this functionality is off.

## 1.2.36

- Added shell functionality for setting Service ID, Token, enabling/disabling Fastly, uploading default VCL, testing connection and cleaning configuration cache.

## 1.2.35

- VCL optimizations and fixes https://github.com/fastly/fastly-magento2/pull/117

## 1.2.34

- Fix for serialization issue regarding old config data for GeoIP Country Mapping (Magento version above 2.2)
- Added shell function for converting Fastly config data to JSON manually (Magento version above 2.2), executed by: fastly:format:serializetojson
- Added shell function for converting Fastly config data to serialize format manually (Should be used only to revert changes made from fastly:format:serializetojson), executed by: fastly:format:jsontoserialize

## 1.2.33

- Don't cache /customer/section/load. This works around core bug where Cache-Control headers are set to cache https://github.com/fastly/fastly-magento2/pull/111
- Due to the way Fastly plugin is implemented we are still sending Varnish like purges which don't do anything https://github.com/fastly/fastly-magento2/pull/110. This fixes it so it doesn't send those
- When Force TLS is enabled if a user request comes in with Google Analytics arguments those will be stripped before issuing a redirect. https://github.com/fastly/fastly-magento2/pull/112 fixes it so redirect is issued immediately before any other logic executes

## 1.2.32

- Remove errant logging when checking if a feature is enabled or not https://github.com/fastly/fastly-magento2/pull/108
- Enable long caching of signed assets https://github.com/fastly/fastly-magento2/pull/109
- Fix for Surrogate Keys not being set on HTML assets when shielding is turned on

## 1.2.31

- Fix for when adding first entry to an ACL modal is incorrectly displayed https://github.com/fastly/fastly-magento2/pull/105

## 1.2.30

- Fix for GeoIP processed

## 1.2.29

- Fix for category ESIs not being correctly purged https://github.com/fastly/fastly-magento2/pull/101

## 1.2.28

- Fix for missing observers. Relates to MAGETWO-70616 issue

## 1.2.27

- Error/maintenance page was returning 503 OK when returning a response. This has now been change 
  503 Service Temporarily Unavailable
- Magento 2.2 changes HTTP API which break PUT requests. This release contains fix for 2.2

## 1.2.26

- VCL clean up. Remove unused structures. Add few more guardrails

## 1.2.25

- Add fastly-page-cacheable debug header to indicate whether a page is cacheable. Helpful to determine if a particular
  block in the page has been marked uncacheable

## 1.2.24

- Fix for a bug where 302 may be deemed an unsuccessful code resulting in caching of cookies

## 1.2.23

- Add Basic Authentication functionality - ability to protect your site during maintenance or development

## 1.2.21

- Add Edge ACLs management interface directly into the Magento Plugin admin

## 1.2.20

- Add Historical bandwidth/request/error stats to the Magento Dashboard

## 1.2.19

- Redesign the edge dictionaries interface to use individual actions/calls when adding/removing entries instead
of bulk calls as bulk

## 1.2.18

- Add Edge Dictionaries management interface directly into the Magento Plugin admin

## 1.2.17

- Purge by content type and store was not working due to fallout from the multiple surrogate key purge bug.


## 1.2.16

- Fix for multiple surrogate key purges being incorrectly serialized

## 1.2.15 

- Convert multiple single surrogate key purges to the new single multiple key purges request
- Migrate geo location variables to the new namespace
- Minor bug fixes and clean ups

## 1.2.14 

- Fix multiple purges being sent for a single product/category change


## 1.2.13

- Webhooks code inadvertently broke ability to do setup and upgrades. This fixes it.

## 1.2.12

- Add ability to add WebHooks for purges and configuration changes

## 1.2.11

- Remove Download VCL button and custom VCL as it's deprecated
- Fix an issue with error/maintenance page where contents were not being escaped causing some elements to be invisible while editing
- Add usage statistics tracking

## 1.2.10

- Mark custom VCL separately

## 1.2.9

- Add new shield locations
- Error page fixes

## 1.2.7

- Add a UI to add a custom error/maintenance page

## 1.2.6

- Add a check to make sure user has saved config before attempting upload

## 1.2.5

- Add Backend Settings configuration - allows reconfiguration of existing backends
- Minor bug fixes

## 1.2.4

- Add Force TLS button in advanced settings - it enables/disables it in the Fastly service

## 1.2.3

- Fix VCL if user has uploaded custom VCL and changed req.url

## 1.2.2

- Minor VCL optimizations

## 1.2.1

- Fix for VCL Snippet upload when no snippets exist

## 1.2.0

- Convert to using VCL snippets https://docs.fastly.com/guides/vcl-snippets/. This will provide for better maintainability since it breaks
down functionality into separate files instead of one large file. Also it avoids the need for having VCL upload functionality enabled
- Button to Test Credentials

## 1.0.9

- Updated etc/fastly.vcl to remove set-cookies on static content. Also to
cache static 404's for 5 minutes.

## 1.0.8

- Some styling changes to better match Magento's style

## 1.0.7

- bumped version.

## 1.0.6

- Corrected autoload typo.

## 1.0.5

- Corrected spelling mistake in README.
- Adjusted dependencies for 2.1 installation issue.

## 1.0.4

- Fixed compilation errors due to dependency declaration

## 1.0.3

- Fixed display of cache management.

## 1.0.2

- Added CLI command to generate VCL

## 1.0.1

- Updates to README.
- Adjustments from Marketplace review.
- Resolved Geo-IP ESI tag being added when Geo-IP function is disabled.
- Adjusted cache headers for caching of Geo-IP response

## 1.0.0

- Changed module name for marketplace review

## 0.9.0

Initial release for testing.
