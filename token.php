<?php
/**
 * Created by IntelliJ IDEA.
 * User: solomonliu
 * Date: 2017-03-21
 * Time: 15:25
 */

require_once 'server.php';

$server->handleTokenRequest(OAuth2\Request::createFromGlobals())->send();