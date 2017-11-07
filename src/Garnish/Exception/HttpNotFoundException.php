<?php

namespace Creativestyle\Garnish\Exception;


class HttpNotFoundException extends HttpResponseException
{
    /**
     * @param string $message
     */
    public function __construct($message = '')
    {
        parent::__construct(404, $message);
    }
}