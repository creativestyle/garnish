<?php

namespace Creativestyle\Garnish\Fetcher;


interface FileFetcherInterface
{
    /**
     * @param string $uri
     * @return FetchedFile
     * @throws FileCouldNotBeFetchedException
     */
    public function fetchFile($uri);

    /**
     * Should unlink any temporary files created.
     * Only those created after specified date.
     *
     * The files must be present for some time after original request ends so the server can send them with X-Sendfile.
     * Defer the cleanup by at least 5 minutes. If a large amount of files accumulates then it's prudent to delete
     * them in batches as this code is subject to timeout and will be executed in a request after headers are sent.
     */
    public function cleanup();
}