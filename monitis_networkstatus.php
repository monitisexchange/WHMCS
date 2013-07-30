<?php
define("CLIENTAREA",true);

require("dbconnect.php");
require("includes/functions.php");
require("includes/clientareafunctions.php");

$pagetitle = $_LANG['clientareatitle'];
$pageicon = "images/support/Support.gif";
$breadcrumbnav = '<a href="index.php">'.$_LANG['globalsystemname'].'</a>';
$breadcrumbnav .= ' > <a href="services.php">Web and Design Services</a>'; 
initialiseClientArea($pagetitle,$pageicon,$breadcrumbnav);

logActivity("MONITIS CLIENT LOG ***** monitis_networkstatus php page");

$smartyvalues["variablename"] = $value; 

//$templatefile = "services"; 
$templatefile = "monitis_networkstatus"; 

outputClientArea($templatefile)

?>