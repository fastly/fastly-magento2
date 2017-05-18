# Fastly_Cdn Release Notes

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
