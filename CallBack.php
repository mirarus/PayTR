<?php

require 'PayTR.php';

$paytr = new PayTR();

$paytr->setConfig([
	'type' => 'callback', # Config Type
    'merchant_key' => '****************', # PayTR Merchant Key
    'merchant_salt' => '****************', # PayTR Merchant Salt
]);

$result = $paytr->callback();

if ($result['status'] != 'success') {
	# Failed Action
	echo "ERROR";
} else{
//	echo "<pre>";
//	print_r($result);
//	echo "</pre>";
	# success Action
	echo "OK";
	exit();
}