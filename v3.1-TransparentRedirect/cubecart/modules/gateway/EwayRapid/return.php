<?php
/* Chronopay malforms the return URL so we have to bring it to a *static* URL. */
$AccessCode = $_GET['AccessCode'];
if (isset($_GET['amp;AccessCode'])) $AccessCode = $_GET['amp;AccessCode'];
$_GET = array(
	'_g' => 'rm',
	'type' => 'gateway',
	'cmd' => 'process',
	'module' => 'EwayRapid',
	'AccessCode' => $AccessCode
);
require('..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'index.php');