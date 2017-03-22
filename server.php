<?php
/**
 * Created by IntelliJ IDEA.
 * User: solomonliu
 * Date: 2017-03-20
 * Time: 22:58
 */

require_once 'oauth2-server-php/src/OAuth2/Autoloader.php';
OAuth2\Autoloader::register();

require_once 'Storage.php';

$config = array(
    'issuer' => "localhost", //the hostname of this server
    'use_openid_connect' => true,
    'always_issue_new_refresh_token' => true,
//    'refresh_token_lifetime'=> 0, //0 = infinite lifetime; default = 2 weeks
    'enforce_redirect' => true,
    'enforce_state' => true
);
$storage = new Storage();
$server = new OAuth2\Server($storage, $config);