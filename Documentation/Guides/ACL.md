# ACL

This guide will show how to add Fastly ACL to your site. 

To add ACL, go to:
```
Magento admin > Stores > Configuration > Advanced > System > Full Page Cache > Fastly Configuration
```
Under *Edge acl* tab, new ACL can be created by clicking **Add ACL** button

![ACL](../images/guides/acl/acl.png "ACL")

After adding new ACL container, a popup will appear in which name of ACL must be entered, and if you want
ACL active from new version, a checkbox for activation must be ticked.

![ACL_Add_Container](../images/guides/acl/acl-container.png "ACL Add Container")

After adding ACL container we can add new items that will belong to this ACL. 
To add new items we click on gear right of newly created entry under *Edge acl* tab which will trigger a popup.

![ACL_Item](../images/guides/acl/acl-item.png "ACL Item")

Under **IP Value** enter the IP you want to handle. It is also possible to enter IP range, example: 192.168.1.0/24.
If you want to negate IP entry (make it blacklisted), just tick **Negated** checkbox. 
Click **Save** button to the right of the newly created entry to save, or click **Delete** button to remove the entry.