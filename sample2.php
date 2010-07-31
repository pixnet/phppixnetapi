<?php

/**
 * IN http://mysite.com/pixnet/start
 */
$api = new PixAPI('__CONSUMER_KEY__', '__CONSUMER_KEY__');
list($request_token, $request_token_secret) = $api->getRequestTokenPair();
// 記錄起來 $request_token & $request_token_secret
$_SESSION['request_token_' . $request_token] = $request_token_secret;
header('Location: ' . $api->getAuthURL('http://mysite.com/pixnet/return'));

/**
 * IN http://mysite.com/pixnet/return
 */
$api = new PixAPI('__CONSUMER_KEY__', '__CONSUMER_KEY__');
$api->setToken($_GET['request_token'], $_SESSION['request_token_' . $_GET['request_token']);
list($access_token, $access_token_secret) = $api->getAccessToken($_GET['verifier_token']);
// 將 $access_token 和 $access_token_secret 記錄起來，以後就直接使用 $api->setToken($access_token, $access_token_secret) 就可以作 API 的動作了。
$_SESSION['access_token'] = $access_token;
$_SESSION['access_token_secret'] = $access_token_secret;

/**
 * IN http://mysite.com/pixnet/getinfo
 */
$api = new PixAPI('__CONSUMER_KEY__', '__CONSUMER_KEY__');
$api->setToken($_SESSION['access_token'], $_SESSION['access_token_secret']);
print_r($api->user_get_account());
