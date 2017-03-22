#!/usr/bin/env bash

#create tables
php -r '
require_once "server.php";
$storage->initDb();
' &&

#create client information
php -r '
require_once "server.php";
$clientId = base64_encode(random_bytes(9));
$clientSecret  = base64_encode(random_bytes(33));
$storage->setClientDetails($clientId, $clientSecret, "https://develop.kidaptive.com/v3/openid/user/ajax-cb", "authorization_code refresh_token", "openid offline_access");
echo "client_id: ".$clientId."\n";
echo "client_secret: ".$clientSecret;
' > clientInfo &&

#generate rsa key pair
php -r '
require_once "server.php";
$output = array();
exec("openssl genrsa 2048", $output);
$key = implode("\n",$output);
$storage->setPrivateKey($key);
echo $key."\n";
' | openssl rsa -pubout -out key.pub