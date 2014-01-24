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

$smartyvalues["variablename"] = $value; 

$templatefile = "monitis_monitors"; 
outputClientArea($templatefile)
?>