<?php

namespace Creativestyle\Garnish\Exception;


use Symfony\Component\HttpFoundation\Response;

abstract class HttpResponseException extends \RuntimeException
{
    /**
     * @param int $code HTTP response code
     * @param string $message
     */
    public function __construct($code, $message = '')
    {
        if (empty($message)) {
            $message = Response::$statusTexts[$code];
        }

        parent::__construct($message, $code);
    }
}