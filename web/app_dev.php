<?php

set_time_limit(30);

use Creativestyle\Garnish\App;
use Symfony\Component\HttpFoundation\Request;

require __DIR__.'/../vendor/autoload.php';

$app = new App(true);

$request = Request::createFromGlobals();
$response = $app->handleRequest($request);
$response->send();
$app->processDeferred();