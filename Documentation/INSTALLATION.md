# Fastly_Cdn Module Installation Instructions

## Contents

- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Troubleshooting](#troubleshooting)

## Prerequisites

Before installing the Fastly_Cdn module you should setup a
test environment as you will need to put Fastly in front which will certainly
take a while for configuring and testing. If you directly roll out this
solution to your production server you might experience issues that could
affect your normal business.

Ensure that your Magento2 store is running without any
problems in your environment as debugging Magento2 issues with Fastly in front
might be difficult.

Fastly_Cdn supports Magento2 Community and Enterprise Edition from version 2.0
onwards.

You need an account with [fastly.com](https://www.fastly.com/signup) which allows
[uploading of custom VCL](https://docs.fastly.com/guides/vcl/uploading-custom-vcl).
If you need professional services for assistance with setting up your
environment please contact magento@fastly.com.

To use the module make sure that your server does not compress text content. This
would prevent the ESI functionality.

## Installation

The tasks involved in this are:

- Add the Fastly_Cdn module to the Magento server. [(Instructions)](#magento-module)
- Configure the Fastly_Cdn module on the Magento server. [(Instructions)](#configure-the-module)
- Configure Fastly's service with the VCL. [(Instructions)](CONFIGURATION.md)

### Magento module

The installation of the Magento module is pretty easy and can be performed in
a few ways depending on your Magento version.

- [Install from Magento Marketplace (only Magento 2.0.x versions)](#installing-from-the-magento-marketplace-using-web-setup-wizard)
- [Install using Composer](#installing-using-composer)
- [Install from Zip file](#installing-from-zip-file)

#### Installing from the Magento Marketplace using Web Setup Wizard

This will require an account with Magento Commerce and the associated
[API keys](http://devdocs.magento.com/guides/v2.0/install-gde/prereq/connect-auth.html)
will be used to sync with the marketplace.

1. Open a browser to the [Magento Marketplace](https://marketplace.magento.com/fastly-magento2.html)
   and add the module to the cart. Check out and ensure that this is added to
   your account.
1. Log into the admin section of the Magento system in which to install the
   module as an administrator.
1. Start the Web Setup Wizard by navigating to 'System > Web Setup Wizard'.
1. Click 'Component Manager' to synchronise with Magento Marketplace.
1. Click to 'Enable' the Fastly_Cdn module. This will start the wizard.
1. Follow the on screen instructions, being sure to create backups.
1. Proceed to [Configuring the Module](CONFIGURATION.md).

#### Installing using Composer

1. Login (or switch user) as the Magento filesystem owner.
1. Ensure that the files in 'app/etc' under the Magento root are write enabled
    by the Magento Filesystem owner.
1. Ensure that Git and Composer are installed.
1. Inside the Magento Home directory add the composer repository for the module.

    ```
    composer config repositories.fastly-magento2 git "https://github.com/fastly/fastly-magento2.git"
    ```

1. Next fetch the module with:

    ```
    composer require fastly/magento2
    ```

1. Once the module fetch has completed enable it by:

    ```
    bin/magento module:enable Fastly_Cdn
    ```

1. Then finally the clean up tasks:

    ```
    bin/magento setup:upgrade
    ```

    followed by:

    ```
    bin/magento cache:clean
    ```

1. Once this has completed log in to the Magento Admin panel and proceed to
    [Configuring the Module](CONFIGURATION.md).

#### Installing from zip file

1. Open a browser to [GitHub](https://github.com/fastly/fastly-magento2/releases)
    note/copy the URL of the version to install.
1. Log in to the Magento server as the Magento filesystem owner and navigate to
    the Magento Home directory.
1. Create a directory `<magento home>/app/code/Fastly/Cdn/` and change directory
    to the new directory.
1. Download the zip/tarball and decompress it.
1. Move the files out of the `fastly-magento2` into
    `<magento home>/app/code/Fastly/Cdn/`.
4. At this point, it is possible to install with either the: 
   
   **Web Setup Wizard's Component Manager (only Magento 2.0.x versions, for 2.1.x use command line)**
   1. To install in the Web Setup Wizard. Open a browser and log in to the Magento
       admin section with administrative privileges.
   1. Navigate to 'System > Web Setup Wizard'.
   1. Click 'Component Manager' scroll down and locate 'Fastly_Cdn'. Click enable
       on the actions.
   1. Follow the on screen instructions ensuring to create backups.
   
   **Command line**
      1. To enable the module on the command line change directory to the Magento
       Home directory. Ensure you are logged in as the Magento filesystem owner.
      1. Verify that 'Fastly_Cdn' is listed and shows as disabled: `bin/magento
       module:status`.
      1. Enable the module with: `bin/magento module:enable Fastly_Cdn`.
      1. Then we need to ensure the configuration tasks are run: `bin/magento
       setup:upgrade`.
      1. Finally on the command line to clear Magento's cache run: `bin/magento
       cache:clean`.

1. Once this has been completed log in to the Magento Admin panel and proceed
    to [Configuring the Module](CONFIGURATION.md).

### Troubleshooting

- Ensure that your Magento version is tested and supported with the version of
  Fastly_Cdn that you are using.

- Ensure that all files and folders have the correct permissions for the
  Magento Filesystem Owner and the Web Server user.

- Ensure that the cache's are cleaned and disable / re-enable the module.

- Ensure that text content is not being compressed.

If any critical issue occurs you can't easily solve, execute
`bin/magento module:disable Fastly_Cdn` as the Magento Filesystem Owner to
disable the Fastly_Cdn module. If necessary clear Magento's cache again.

If possible, gather as much data and [open an issue](OPENING-ISSUES.md) to
help improve the module.
