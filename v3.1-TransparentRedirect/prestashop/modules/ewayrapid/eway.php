<?php

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
echo "<link href='" . _THEME_CSS_DIR_ . "global.css' rel='stylesheet' type='text/css' media='all' />";
include(dirname(__FILE__).'/ewayrapid.php');

if (!$cookie->isLogged())
	Tools::redirect('index.php?controller=authentication&back=order.php');

$Ewayrapid = new Ewayrapid();
$response = $Ewayrapid->GetAccessCodeResult();

include_once(dirname(__FILE__).'/../../footer.php');

?>