<?php

namespace Creativestyle\Garnish\Middleware;


use Creativestyle\Garnish\Fetcher\FetchedFile;

interface MiddlewareInterface
{
    /**
     * @param FetchedFile $fetchedFile
     * @param array $parameters
     * @return FetchedFile
     */
    public function process(FetchedFile $fetchedFile, array $parameters);

    /**
     * @return array
     */
    public function parameterNames();
}
