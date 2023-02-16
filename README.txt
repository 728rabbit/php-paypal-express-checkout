Step 1, do Checkout:
---------------------------------------------------------
$item = [
    [
        'name' => 'xxxxx',
        'price' => 2.00,
        'qty' => 2
    ],
    [
        'name' => 'yyyyy',
        'price' => 1.00,
        'qty' => 3
    ]
];

$shipTo = [
    'name' => 'chan tai man',
    'email' => 'xxx@test.com',
    'street' => '-',
    'city' => '-',
    'state' => '',
    'country_code' => 'HK',
    'zip' => '000000',
    'street2' => '',
    'phone_num' => '',
];

$paypal_api = new \App\Libraries\PaypalApi();
$paypal_api->shippingAmount(1);
$paypal_api->shippingDiscount(0.2);
$paypal_api->returnURL('http://xxxx.com/feedback');
$paypal_api->cancelURL('http://xxxx.com');
$paypal_url = $paypal_api->checkout($item, $shipTo);
if($paypal_url) {
    header('Location:'.$paypal_url);
    exit();
}


Step 2, confirm Checkout:
---------------------------------------------------------
$paypal_api = new \App\Libraries\PaypalApi();
if($paypal_api->confirm(7.8)) {
    // payment successfully
    // do something here
} 

