# Fastly_Cdn Release Notes

## 1.2.119

- GeoIP fixes https://github.com/fastly/fastly-magento2/pull/314 and https://github.com/fastly/fastly-magento2/pull/311
- Update visibility in getmessageinstorelocale function https://github.com/fastly/fastly-magento2/pull/312

## 1.2.118

- Add notification to update Blocking Config when blocking changes are made https://github.com/fastly/fastly-magento2/pull/310
- Add handling for GraphQL paths. This may be removed in the future after Magento core adds Vary GraphQL requests https://github.com/fastly/fastly-magento2/pull/307
- Add fix for uenc brackets [] when using GeoIP https://github.com/fastly/fastly-magento2/pull/311

## 1.2.117

- Expose all options for configuring the backend. Previously we only exposed only selected fields https://github.com/fastly/fastly-magento2/pull/308

## 1.2.116

- Add notification to upload custom VCL snippets when they are changed

## 1.2.115

- Remove conflicting WAF bypass statement

## 1.2.114

- Add Override Host option to backend creation

## 1.2.113

- Warning when vcl is out of date https://github.com/fastly/fastly-magento2/pull/297

## 1.2.112

- Enhance export functionality to export Edge Module configs https://github.com/fastly/fastly-magento2/pull/295
- Make sure we don't set uenc unless VCL version 1.2.111  https://github.com/fastly/fastly-magento2/pull/296

## 1.2.111

- Add Fastly Version UI https://github.com/fastly/fastly-magento2/pull/293
- Add page URL to the GeoIP switcher https://github.com/fastly/fastly-magento2/pull/292

## 1.2.110

- Rework the rate limiting UI  https://github.com/fastly/fastly-magento2/pull/291
- Allow creation of dictionaries or ACLs from Edge Module configuration screens https://github.com/fastly/fastly-magento2/pull/290

## 1.2.109

- Change composer magento-framework requirement to 101+. This change abandons 2.1.x

## 1.2.108

- Retag of 1.2.103 in order to fix M2.1.x upgrading. 

## 1.2.107

- Fix for 1.2.106 caused issues during checkout https://github.com/fastly/fastly-magento2/pull/288

## 1.2.106

- Fix for missing type in phpdoc which results in failed compilation https://github.com/fastly/fastly-magento2/pull/286

## 1.2.105

- Another fix for 2.3 IO - add orientation and canvas https://github.com/fastly/fastly-magento2/pull/284

## 1.2.104

- Fix for "Catalog list image optimization not working in Magento" https://github.com/fastly/fastly-magento2/pull/283

## 1.2.103

- Add Blackfire integration edge module
- Add Time Edge module was last uploaded https://github.com/fastly/fastly-magento2/pull/278

## 1.2.102

- Added uenc to the GeoIP storeswitcher https://github.com/fastly/fastly-magento2/pull/276
- add UI to create backends https://github.com/fastly/fastly-magento2/pull/274

## 1.2.101

- Add Gzip safety logic to avoid default Gzip policy interfering with ESI processing

## 1.2.100

- Add Edge Module to integrate other CMS/Backend
- Additional fixes to the Edge Module

## 1.2.99

- Bugfix for edge modules losing group values https://github.com/fastly/fastly-magento2/pull/268

## 1.2.98

- Add Increase Timeouts for Long Running jobs edge module
- Change req.http.Fastly-FF references to use the new fastly.ff datastructure
- Add definition of snippet priority to edge modules https://github.com/fastly/fastly-magento2/pull/266
- Another pass at removing redundant x-pass request conditions https://github.com/fastly/fastly-magento2/pull/267

## 1.2.97

- Cleaned up redundant x-pass request conditions https://github.com/fastly/fastly-magento2/pull/265
- Fix for boolean mode edge modules not working correctly https://github.com/fastly/fastly-magento2/pull/264
- Edge modules are turned on by default

## 1.2.96

- Fix for maintenance mode not using the custom maintenance/error page

## 1.2.95

- Fix GeoIP not working for stores with different base URL https://github.com/fastly/fastly-magento2/pull/263

## 1.2.94

- Fix for Auto WebP not being set
- Experimental support for rate limiting abusive crawlers 

## 1.2.93.

- Experimental support for rate limiting https://github.com/fastly/fastly-magento2/pull/259

## 1.2.92

- Improvements to maintenance mode support https://github.com/fastly/fastly-magento2/pull/258

## 1.2.91

- Add maintenance mode support https://github.com/fastly/fastly-magento2/pull/256
- Added check for file and line array indexes for webhook stack trace https://github.com/fastly/fastly-magento2/pull/257
- Changed the way the store switch url parameters are added for the geoip redirect https://github.com/fastly/fastly-magento2/pull/254

## 1.2.90

- Add Bypass Fastly cache for Admin users Admin module https://github.com/fastly/fastly-magento2/commit/56595f105b4ccf8b4b70dc2a418456fcdef94fe7

## 1.2.89

- Rework ACL interface to more closely align it with Fastly interface https://github.com/fastly/fastly-magento2/pull/252

## 1.2.88

- Change shield definition for Tokyo

## 1.2.87

- There are multiple locations to set image quality e.g. Fastly has IO defaults menu with quality settings that are used unless
  quality query argument exists in the URL. Deep IO optimization sets the default quality level by appending the quality argument
  This pull request exposes the latter in the UI under Deep IO https://github.com/fastly/fastly-magento2/pull/251

## 1.2.86

- Remove snippets when Edge Module is disabled https://github.com/fastly/fastly-magento2/pull/250

## 1.2.85

- When removing custom snippet also remove them from Fastly https://github.com/fastly/fastly-magento2/pull/249

## 1.2.84

- Flush Magento cache used to flush Fastly as well. This changes to behavior to Magento Only https://github.com/fastly/fastly-magento2/issues/246

## 1.2.83

- Make sure Quick Purge uses the PURGE verb https://github.com/fastly/fastly-magento2/pull/245

## 1.2.82

- Fix for gstatic.com minification that was done in 1.2.79 broke under Magento 2.1.x. This fixes it https://github.com/fastly/fastly-magento2/pull/244

## 1.2.81

- Added more details to quick purge error messages https://github.com/fastly/fastly-magento2/pull/243

## 1.2.80

- Correct historic stats URL path. https://github.com/fastly/fastly-magento2/pull/241

## 1.2.79

- Addition of the interface to manage domains https://github.com/fastly/fastly-magento2/pull/240
- Add fix to avoid magento for rewriting gstatic.com assets that are no minified. Without this it breaks Fastly usage graphs in the dashboard https://github.com/fastly/fastly-magento2/pull/239

## 1.2.78

- Fix for stock Magento placeholder images being displayed instead of customer defined when deep IO turned on  https://github.com/fastly/fastly-magento2/pull/236

## 1.2.77

- Stop rewriting version assets URLs in Varnish https://github.com/fastly/fastly-magento2/pull/230
- Add ability to configure WAF ACL Bypass https://github.com/fastly/fastly-magento2/pull/232

## 1.2.76

- Added ___from_store url parameter when switching stores https://github.com/fastly/fastly-magento2/pull/228
- Changed the way Fastly Statistics obtain default site country name https://github.com/fastly/fastly-magento2/pull/227

## 1.2.75

- Changed popup.js name and any references to it to 'overlay' to avoid potential adblocking https://github.com/fastly/fastly-magento2/pull/224
- Handlebars ifEq helper fix https://github.com/fastly/fastly-magento2/pull/223
- Bugfix/#199 module breaks search engine switcher due to testconnection same name https://github.com/fastly/fastly-magento2/pull/222
- Reset cache-control headers to uncacheable only if X-Magento-Tags header is present https://github.com/fastly/fastly-magento2/commit/c99d56bf96c627cfec5205b258a102b6e549fa97

## 1.2.74

- Code refactoring and add comments to Fastly service config changes so they show up in event log https://github.com/fastly/fastly-magento2/pull/219
- Preliminary support for Fastly Edge Modules. They are off by default. Need to be enabled through the Advanced menu. https://github.com/fastly/fastly-magento2/pull/218

## 1.2.73

- Add store code to CountryMapping list to identify store https://github.com/fastly/fastly-magento2/pull/216
- Initial implementation of the Web Application Firewall (WAF) https://github.com/fastly/fastly-magento2/pull/217

## 1.2.72

- Added check for empty admin user variable in webhooks https://github.com/fastly/fastly-magento2/pull/215

## 1.2.71

- Added save to config when update blocking is triggered https://github.com/fastly/fastly-magento2/pull/213

## 1.2.70

- Refine blocking to include allowlist functionality https://github.com/fastly/fastly-magento2/pull/211

## 1.2.69

- Change default setting to preserve static content e.g. JS/CSS/Images when people request Flush Magento Cache. This should provide for higher cache hit ratio

## 1.2.68

- Fix for non square images and canvas setting

## 1.2.67

- Allow to turn off canvas query option to image optimization https://github.com/fastly/fastly-magento2/pull/209
- Code cleanup and refactoring https://github.com/fastly/fastly-magento2/pull/208

## 1.2.66

- Added check for empty string instead of just false, added default config value to force lossy https://github.com/fastly/fastly-magento2/pull/207

## 1.2.65

- Fix oversight where objects with no Cache-control and Expires headers would end up with the default TTL
- Add canvas parameter to product images https://github.com/fastly/fastly-magento2/pull/206

## 1.2.64

- Expose admin's username in slack actions https://github.com/fastly/fastly-magento2/pull/200
- Added option to toggle bg-color query argument for images https://github.com/fastly/fastly-magento2/pull/198

## 1.2.63

- Add additional tunable to send full stack trace for all purge actions not just purge all https://github.com/fastly/fastly-magento2/pull/196

## 1.2.62

- Fix for situations where image is unavailable and placeholder image is inserted https://github.com/fastly/fastly-magento2/pull/195

## 1.2.61

- Rework how snippets are written to disk. Addresses issues with Magento Cloud https://github.com/fastly/fastly-magento2/pull/194

## 1.2.60

- Fix for https://github.com/fastly/fastly-magento2/issues/193

## 1.2.59

- Fix for https://github.com/fastly/fastly-magento2/issues/191

## 1.2.58

- Remove GeoIP processed cookie constant as it's not used and may be interpreted as tracking for GDPR https://github.com/fastly/fastly-magento2/pull/188
- Add ability to upload custom VCL snippets https://github.com/fastly/fastly-magento2/pull/179
- Add validation for Admin path timeout. It needs to be between 0 and 600 seconds. https://github.com/fastly/fastly-magento2/pull/189
- Add HSTS headers when force TLS is enabled https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Strict-Transport-Security

## 1.2.57

- Add ability to force lossy conversions of lossless image formats https://github.com/fastly/fastly-magento2/pull/186

## 1.2.56

- Wording/documentation changes
- By default remove User-Agent Vary from backend responses https://github.com/fastly/fastly-magento2/pull/181

## 1.2.55

- VCL reordering to address caching of 404s during site rebuilds https://github.com/fastly/fastly-magento2/issues/174

## 1.2.54

- Fix for Redis sessions contention when where Fastly module makes multiple parallel requests 
  to find out when certain features are enabled. This change changes it to be on demand versus bulk 
  https://github.com/fastly/fastly-magento2/pull/177
- Add ability to customize WAF blocking page https://github.com/fastly/fastly-magento2/pull/175

## 1.2.53

- Minor wording changes around Image Optimization

## 1.2.52

- We are marking any pages with ESIs as such https://github.com/fastly/fastly-magento2/pull/172. This avoids issues
with slow pages waiting for full payload to be processed by ESI engine
- Add ability to tweak default Image Optimization settings https://github.com/fastly/fastly-magento2/pull/171

## 1.2.51

- Revert since it caused issues with ESIs https://github.com/fastly/fastly-magento2/pull/166

## 1.2.50

- Feature/check if io is enabled https://github.com/fastly/fastly-magento2/pull/167
- Added check for the error/maintenance page HTML character count https://github.com/fastly/fastly-magento2/pull/168

## 1.2.49

- Changed device pixel ratios checkboxes to multiselect https://github.com/fastly/fastly-magento2/pull/165

## 1.2.48

- Enhancement to adaptive pixel ratios to allow users to select ratios they want to support https://github.com/fastly/fastly-magento2/pull/161

## 1.2.47

- Add ability to remove edge dictionaries and ACLs https://github.com/fastly/fastly-magento2/pull/157
- Resort ordering of config tabs
- Add adaptive pixel https://github.com/fastly/fastly-magento2/pull/160

## 1.2.46

- Stop treating every HTML file as potentially having ESIs. We'll mark all Magento documents as ESIs
- Fix broken Basic Auth upload
- Fix broken GeoIP

## 1.2.45

- Add preliminary implementation for Image Optimization
- Fix for system configuration bar being broken https://github.com/fastly/fastly-magento2/pull/152

## 1.2.44

- Updates to the blocking UI https://github.com/fastly/fastly-magento2/pull/146
- Improvements to the limiting X-Magento-Tags https://github.com/fastly/fastly-magento2/pull/145
- Minor bug fixes and code clean up

## 1.2.43

- Bugfixes encountered when doing refactoring for MEQP2

## 1.2.42

- Changes to achieve Magento Extension Quality Program (MEQP) compliance
- Add UI to add blocking by country and ACL https://github.com/fastly/fastly-magento2/pull/137
- Make sure the X-Magento-Tags header is less than 16kBytes in length

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
