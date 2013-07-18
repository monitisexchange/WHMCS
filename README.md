# Monitis Addon for WHMCS

This addon for WHMCS provides integration with the Monitis platform, including
one-click creation of monitors for WHMCS hosting servers.

## Installation

Copy or clone the repository to your WHMCS addon module directory  

<pre>
    $ cd /var/www/html/whmcs/modules/addons/
    $ git clone https://github.com/monitisexchange/WHMCS.git monitis_addon
</pre>

Activate the addon.  
This will require supplying your Monitis API credentials.  
(You should have Monitis ACCOUNT)

## Usage

Once the addon is installed and activated, a "Monitis" option will be
available in the Addons menu.  Selecting this menu item will bring you to
the addon panel, showing:

* Monitored and unmonitored servers, with options for adding and removing
  monitoring
* Previously created monitors for servers that have been deleted
* Links for detailed view of monitored servers, including current performance
  graph
