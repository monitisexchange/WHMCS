## Monitis Addon for WHMCS
Monitis addon for WHMCS provides integration with the [Monitis](http://monitis.com) platform including: 
automation of monitor setup and management both for hosting companies and their customers
direct access to monitoring data and statuses on WHMCS admin panel
automated monitoring products / product addons setup and configuration

#### Installation
Copy or clone the repository and you can install it as follows:  

1. monitis_addon folder needs to be uploaded to the /modules/addons/ directory of your WHMCS installation
2. upload monitis_monitors.php to the WHMCS root folder
3. upload monitis_monitors.tpl to the template folder 
4. add new tab in the main navigation template (header.tpl) and set link to the page monitis_monitors.php 
5. you then need to navigate to Setup > Addon Modules within your WHMCS Admin Area to activate and configure the module
6. upon activation, you will need to configure "Access Control" settings. 
Additional settings relating to the Monitis addon configuration are provided within the addon itself.
Access Control - These checkboxes allow you to define which role groups you want to allow to access and use the project management system
Once you're done configuring the access rights, click Save to complete the process.

#### Getting Started
To access the Monitis addon, simply navigate to Addons > Monitis Addon.  
First, you need to provide your Monitis account credentials (API key and Secret key, available from your Monitis dashboard: Account >API key) on Monitis Account tab.  
If you do not have Monitis account, please, [signup](https://portal.monitis.com/free-signup).  
Next step is configuration of settings for predefined monitor types Addons > Monitis Addon > Settings. Here you define default monitor types (server and network) and configurations.  
When saving the defined settings you will be offered to automatically create monitors for all your existing servers.  

#### Monitoring your infrastructure
There is no need to log into Monitis dashboard to create required monitors or to access monitoring results.  
All the information needed for monitoring your server base is located on Monitis Addon > Servers and Monitis Addon > Monitors tabs.   
In order to automate monitor creation for existing or newly provisioned servers information provided in Hostname and IP Address fields of your servers (Setup > Products/Services > Servers) will be used.  
Please, be very careful when filling in these fields.  
IP Address will be used for creating uptime monitor. Hostname will be used to identify Monitis Agent installed on the server. It is required that Monitis Agent Name is set to the hostname.  
Please, note that Monitis addon doesn’t automate Monitis Agent installation on your servers.   

#### Monitoring for web hosting clients
Monitis addon makes possible to offer monitoring to your clients as a product or product addon.  
On Monitis Addon > Products tab you will see all your products and configure available monitor types for the product.  
When activating a product as Monitis monitoring, two new custom fields will be created for that product – URL/IP and Monitor Type.  
Information provided in these fields will be used for creation of monitor upon order activation. 


