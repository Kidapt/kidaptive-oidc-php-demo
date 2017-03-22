<?php
/**
 * Created by IntelliJ IDEA.
 * User: solomonliu
 * Date: 2017-03-22
 * Time: 03:14
 */

require_once 'server.php';
$response = new OAuth2\Response();

if ($server->verifyResourceRequest(OAuth2\Request::createFromGlobals(), $response)) {
    $userId = $server->getResourceController()->getToken()['user_id'];
    $userInfo = $storage->getUser($userId);
    $response->addParameters($userInfo);
}

$response->send();