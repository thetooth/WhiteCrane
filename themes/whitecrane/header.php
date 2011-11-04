<?php
$time_start = microtime ( true );
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php
echo $dew->_PageTitle ( WC_Page ) . ' - ' . $dew->_config ['site_title'];
?></title>
<link rel="stylesheet" href="themes/whitecrane/style.css"
	type="text/css" />
<?php
$dew->META ();
?>
</head>
<body>
<div align="right"><a class="home" href="<?php
echo WC_SELF;
?>">-</a></div>
<div id="maincontent">