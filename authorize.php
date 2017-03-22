<?php
/**
 * Example code for OAuth 2.0 authorization endpoint
 * User: solomonliu
 * Date: 2017-03-21
 * Time: 13:00
 */

require_once 'server.php';

$request = OAuth2\Request::createFromGlobals();
$auth = false;
$userId = false;

if (array_key_exists('username', $request->request) && array_key_exists('password', $request->request)) {
    $username = $request->request['username'];
    $password = $request->request['password'];
    $auth = $storage->checkUserCredentials($username, $password);
    $userId = $storage->getUserId($username);
}

$server->handleAuthorizeRequest($request, new OAuth2\Response(), $auth, $userId)->send();