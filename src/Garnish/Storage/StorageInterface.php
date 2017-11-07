<?php

namespace Creativestyle\Garnish\Storage;


use Creativestyle\Garnish\Fetcher\FetchedFile;
use Symfony\Component\HttpFoundation\Response;

interface StorageInterface
{
    /**
     * @param FetchedFile $file
     * @param string $uri
     * @param array $parameters
     * @return
     */
    public function store(FetchedFile $file, $uri, array $parameters = []);

    /**
     * @param string $uri
     * @param array $parameters
     * @return Response
     */
    public function serve($uri, array $parameters = []);

    /**
     * @param string $uri
     * @param array $parameters
     * @return bool
     */
    public function exists($uri, array $parameters = []);

    /**
     * @param \DateTime $since
     * @return int Number of files removed.
     */
    public function cleanup(\DateTime $since);
}