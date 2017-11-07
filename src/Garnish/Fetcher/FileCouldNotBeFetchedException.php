<?php

namespace Creativestyle\Garnish\Fetcher;


class FileCouldNotBeFetchedException extends \RuntimeException
{
    /**
     * @var string
     */
    private $url;

    /**
     * @var int
     */
    private $responseCode;

    /**
     * @param string $url
     * @param int $responseCode
     * @param string $errorMessage
     */
    public function __construct($url, $responseCode = null, $errorMessage = null)
    {
        $this->url = $url;
        $this->responseCode = $responseCode;

        $message = sprintf('Fetch error');

        if ($responseCode) {
            $message .= sprintf(' - received code %d', $responseCode);
        }

        if ($errorMessage) {
            $message .= sprintf(' - error message: %s', $errorMessage);
        }

        parent::__construct($message);
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return int
     */
    public function getResponseCode()
    {
        return $this->responseCode;
    }
}