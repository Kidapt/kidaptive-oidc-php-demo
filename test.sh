#!/usr/bin/env bash

auth_endpoint=localhost:8000/authorize.php &&
token_endpoint=localhost:8000/token.php &&
info_endpoint=localhost:8000/userInfo.php &&

username=user1 &&
password=password1 &&

state=$(php -r 'echo base64_encode(random_bytes(33));') &&
nonce=$(php -r 'echo base64_encode(random_bytes(33));') &&
. clientInfo.properties &&
redirect_uri=https://develop.kidaptive.com/v3/openid/user/ajax-cb &&
response_type=code &&
scope="'openid offline_access'" &&
grant_type=authorization_code &&

data=$(echo username password state nonce client_id redirect_uri response_type scope | xargs -n1 | awk '{print "--data-urlencode "$1"=$"$1}') &&
echo Testing authentication... &&
redirect=$(eval eval curl -s -X POST -D /dev/stdout $auth_endpoint $data | grep Location) &&
code=$(echo $redirect | grep -o 'code=[^&]*' | awk -F= '{print $2}') &&
if [ ! $code ]; then echo Failed; exit; fi &&
#TODO: add check for state
echo Success! &&

echo Testing token... &&
data=$(echo redirect_uri grant_type scope code | xargs -n1 | awk '{print "--data-urlencode "$1"=$"$1}') &&
token=$(eval eval curl -s -X POST $token_endpoint -u $client_id:$client_secret $data) &&
access=$(echo $token | grep -o '"access_token":"[^"]*' | grep -o '[^"]*$') &&
id=$(echo $token | grep -o '"id_token":"[^"]*' | grep -o '[^"]*$') &&
refresh=$(echo $token | grep -o '"refresh_token":"[^"]*' | grep -o '[^"]*$') &&
if [ ! $id ]; then echo Failed; exit; fi &&
echo $id &&
echo Success! &&

echo Test userInfo... &&
userInfo=$(curl -s $info_endpoint -H "Authorization: Bearer $access") &&
if [ ! $userInfo ]; then echo Failed; exit; fi &&
echo $userInfo &&
echo Success!