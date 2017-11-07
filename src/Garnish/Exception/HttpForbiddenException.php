<?php

namespace Creativestyle\Garnish\Exception;


class HttpForbiddenException extends HttpResponseException
{
    /**
     * @param string $message
     */
    public function __construct($message = '')
    {
        parent::__construct(403, $message);
    }
}