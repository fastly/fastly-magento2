# Basic Authentication guide

This guide will show how to setup Basic Authentication for your site. Basic auth is useful when you are
in the process of implementing your store and want to prevent public access e.g. disallow crawlers, curious
users from seeing your site. 

All parts of the site will be secured except the Admin site (this is to prevent accidental lockouts).

To enable Basic Authentication, go to:

```
Magento admin > Stores > Configuration > Advanced > System > Full Page Cache > Fastly Configuration
```

Under the *Basic Authentication** tab, you will see a screen like this. 

![Basic Auth Main Screen](../images/guides/basic-auth/main-screen.png "Basic Auth Main Screen")

First thing you will need to do is add user/password pairs for basic auth. To do so click on **Manage Users** button. 
A modal window with the following content will pop up.

![Basic Auth Create Container for Auth Users Modal](../images/guides/basic-auth/create-container-for-authenticated-users.png "Basic Auth Create Container for Auth Users Modal")

press the **Upload button** in the upper right corner. This will create a Fastly Edge Dictionary that is used to store
authentication information. Once done, another modal window will open up that allows you to add username/password pairs for users. 
After adding an entry make sure you click on the save icon (pointed out with a red arrow).

![Basic Auth Manage Users Modal](../images/guides/basic-auth/adding-users.png "Basic Auth Manage Users Modal")

Once the basic auth users have been created click on the **Enable/Disable** button. A modal window will show up. Press the 
**Upload button** in the upper right corner to activate it. 

The modal windows will close and you will see a success message. Also, the current state will change to **enabled**.

![Basic Auth Manage Enabled Screen](../images/guides/basic-auth/confirmation-screen.png "Basic Auth Manage Enabled Screen")


## Turning off Basic Auth

Turn off basic authentication can be achieved by click the **Enable/Disable** button and clicking the upload button in the resulting
modal.

## Removing all users

If you need to start from scratch you can remove all users. Please note this will disable Basic Authentication since no access
will be allowed.
