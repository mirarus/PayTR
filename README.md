# PayTR
Php Class for PayTR Virtual Pos
 
# Creating a Sample Payment Page

```
<?php

require 'PayTR.php'; 

$paytr = new PayTR();

$paytr->setConfig([
    'type' => 'init', # Config Type
    'merchant_id' => '******', # PayTR Merchant ID
    'merchant_key' => '****************', # PayTR Merchant Key
    'merchant_salt' => '****************', # PayTR Merchant Salt
    'merchant_success_url' => 'http://localhost/payment_success.php', # PayTR Success Url
    'merchant_failed_url' => 'http://localhost/payment_failed.php' # PayTR Failed Url
]);

$paytr->setCustomer([
    'name' => 'customer.name', # Customer Name
    'email' => 'customer.mail@gmail.com', # Customer Mail
    'phone' => 'customer.phone', # Customer Phone Number
    'address' => 'customer.address' # Customer Address
]);

$paytr->setLocale('TL');
$paytr->setLang('tr');

$paytr->setProduct([
    'order_id' => 300, # Product Order ID
    'amount' => 5 # Product Amount
]);

/*
$paytr->setItems([
    [
        'name' => "Product 1", # Product Name
        'amount' => 110, # Product Amount
        'piece' => 1, # Product Piece
    ]
]);
*/

echo '<iframe src="https://www.paytr.com/odeme/guvenli/' . $paytr->init() . '" frameborder="0" scrolling="no" width="100%" height="100%"></iframe>';
```

# Creating a Sample CallBack Page

```
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
//  echo "<pre>";
//  print_r($result);
//  echo "</pre>";
    # success Action
    echo "OK";
    exit();
}
```