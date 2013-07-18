<?php

/*
		$result = mysql_query("DROP TABLE `mod_monitis_product`");	
		$query = "CREATE TABLE `mod_monitis_product` (
					`product_id` INT NOT NULL,
					`status` varchar(50),
					PRIMARY KEY ( `product_id` )
					);";
		$result = mysql_query($query);
		
		$result = mysql_query("DROP TABLE `mod_monitis_addon`");
		$query = "CREATE TABLE `mod_monitis_addon` (
					`addon_id` INT NOT NULL,
					`type` varchar(50),
					`status` varchar(50) default 'active',
					PRIMARY KEY ( `addon_id` )
					);";
		$result = mysql_query($query);


		$result = mysql_query("DROP TABLE `mod_monitis_product_monitor`");	
		$query = "CREATE TABLE `mod_monitis_product_monitor` (
					`product_id` INT NOT NULL,
					`type` varchar(50),
					`monitor_id` INT NOT NULL,
					`monitor_type` varchar(50),
					`user_id` INT NOT NULL,
					`ordernum` INT NOT NULL,
					`publickey` varchar(100),
					PRIMARY KEY ( `monitor_id` )
					);";
		$result = mysql_query($query);

*/
require_once ('../modules/addons/monitis_addon/lib/product.class.php');
require_once ('../modules/addons/monitis_addon/lib/services.class.php');


_db_table ( 'mod_monitis_int_monitors' );
_db_table ( 'mod_monitis_ext_monitors' );


//mysql_query('DELETE FROM mod_monitis_int_monitors WHERE server_id=0');





//_db_table ( 'tblorders' );
//_db_table ( 'tblproducts' );

//_db_table ( 'tblproductgroups' );
//_db_table ( 'tblproductconfigoptionssub' );
//_db_table ( 'tblproductconfigoptions' );

_db_table ( 'mod_monitis_product' );
_db_table ( 'mod_monitis_product_monitor' );
_db_table ( 'mod_monitis_addon' );

//_db_table ( 'tblhostingaddons' );
//_db_table ( 'tblhosting' );
//_db_table ( 'tbladdons' );


//_db_table ( 'tbladdons' );
//_db_table ( 'tblproducts' );

//_db_table ( 'tblpricing' );


//_db_table ( 'tblcustomfields' );
//_db_table ( 'tblcustomfieldsvalues' );
//_db_table ( 'tbldomains' );
//_db_table ( 'tbladdonmodules' );
//_db_table ( 'tblregistrars' );
//_db_table ( 'tblwhoislog' );
?>


