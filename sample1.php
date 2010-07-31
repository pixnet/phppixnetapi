<?php

require('PixAPI.php');

$api = new PixAPI('__CONSUMER_KEY__', '__CONSUMER_SECRET__');
echo "Please go to " . $api->getAuthURL() . " to authorization\n";
$verifier_token = readline('input verifier_token: ');
$api->getAccessToken($verifier_token);

print_r($api->user_get_account());
