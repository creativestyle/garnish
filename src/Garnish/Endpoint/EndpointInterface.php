<?php

namespace Creativestyle\Garnish\Endpoint;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface EndpointInterface
{
    /**
     * @param Request $request
     * @return Response
     */
    public function handleRequest(Request $request);
}