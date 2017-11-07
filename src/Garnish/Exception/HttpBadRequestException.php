<?php

namespace Creativestyle\Garnish\Exception;


class HttpBadRequestException extends HttpResponseException
{
    /**
     * @param string $message
     */
    public function __construct($message = '')
    {
        parent::__construct(400, $message);
    }
}