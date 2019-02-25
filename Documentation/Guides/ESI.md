# ESI step-by-step guide

The simplest way to implement an ESI tag in Magento 2 is to to give a "ttl" attribute to a block in a layout file. This should be done in a custom module.

Example:

In a module's `Block` directory, we first create a block file called `Message.php` with the following code:

```
<?php

namespace Vendor\ModuleName\Block;

use Magento\Backend\Block\Template;

class Message extends Template
{
    protected function _construct()
    {
        $this->_template = 'Vendor_ModuleName::message.phtml';
        parent::_construct();
    }

    public function getMessage()
    {
        return 'some message';
    }
}
```

Then in our module `view/frontend/template` directory, we create a `message.phtml` template file containing something like this:
```
<div class="message"> 
    <?php /* @noEscape */ echo $block->getMessage(); ?>
</div>
```

Next, we have to add the block to a layout file for it to render the template on a page. If we want to add our block at the top of the page, in our module `view/frontend/layout` directory we can create a `default.xml` file with the following code:

```
<?xml version="1.0"?>

<page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/page_configuration.xsd">
    <body>
        <referenceContainer name="after.body.start">
            <block class="Vendor\ModuleName\Block\Greeting\Message" name="greeting.message" template="Vendor_ModuleName::message.phtml" ttl="3600"/>
        </referenceContainer>
    </body>
</page>
```
Since the `<block>` tag in our code has the “ttl” attribute, Magento 2 will automatically render it as an ESI tag.

If perhaps we wish to override a default module layout file from within a theme, for example the `vendor/magento/module-catalog/view/frontend/layout/catalog_category_view.xml` layout file, we would have to copy that layout file to our own theme like so: `app/design/frontend/vendor-name/theme-name/Magento_Catalog/layout/catalog_category_view.xml`
and then make similar changes like in the above `default.xml` by placing our block somewhere in the layout.

For things like showing different messages or redirecting users based on their locale, we need to write our blocks a bit differently:
```
<?php

class GetAction extends AbstractBlock
{    
    private $config;   
    private $response;
    
    public function __construct(
        Config $config,
        Context $context,
        Response $response,
        array $data = []
    ) {
        $this->config = $config;
        $this->response = $response;

        parent::__construct($context, $data);
    }

    protected function _toHtml()
    {
        $actionUrl = $this->getUrl('vendorModuleName/message/getaction');

        $header = $this->response->getHeader('x-esi');
        if (empty($header)) {
            $this->response->setHeader("x-esi", "1");
        }

        return sprintf(
            '<esi:include src=\'%s\' />',
            preg_replace("/^https/", "http", $actionUrl)
        );
    }
}
```
In our `default.xml` layout file we should still place our block somewhere, but it will not reference a template file:

```
<referenceContainer name="after.body.start">
    <block class="Vendor\ModuleName\Block\Message\GetAction" name="vendormodulename.message.getaction" />
</referenceContainer>
```
Instead, the ESI tag gets rendered on the page with a url that calls a controller action from our module. The controller retrieves the user’s country code from the geotag in the Fastly request and then uses that code to render the required layout and message depending on the user location.

The controller could contain something like this:
```
...
$resultLayout = $this->resultLayoutFactory->create();
$countryCode = $this->getRequest()->getParam('country_code');
if ($countryCode == ‘US’) {
    $resultLayout->getLayout()->getUpdate()->load(['some_layout']);
}
return $resultLayout
...
```
In this example, the controller retrieves the country code and its value is “US” and based on that value it loads the specified layout file `some_layout` which is located in the module `view/frontend/layout` directory.

That layout file can look something like this:
```
<layout xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/layout_generic.xsd">
    <container name="root" label="Root">
        <block class="Magento\Framework\View\Element\Template" name="us_message" template="Vendor_ModuleName::us_message.phtml"/>
    </container>
</layout>
```
This layout file will render the `us_message.phtml` file (that we need to create) located in the module `view/templates` folder. That file can contain html or even javascript that will only get rendered to users with the `US` geotag.