<?php

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
include(dirname(__FILE__).'/ewaysharedpage.php');

if (!$cookie->isLogged())
	Tools::redirect('index.php?controller=authentication&back=order.php');

$Ewayshared = new Ewaysharedpage();
if( isset($_REQUEST['AccessCode']) ) {
	$response = $Ewayshared->GetAccessCodeResult();
} else {
    $response = $Ewayshared->CreateAccessCodesShared();
}

include_once(dirname(__FILE__).'/../../footer.php');

?>