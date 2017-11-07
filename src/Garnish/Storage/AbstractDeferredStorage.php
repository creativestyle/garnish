<?php

namespace Creativestyle\Garnish\Storage;


use Creativestyle\Garnish\Fetcher\FetchedFile;

/**
 * Base class for deferred storage plugins.
 *
 * Extend it then implement the doStore method.
 */
abstract class AbstractDeferredStorage implements StorageInterface, DeferredStorageInterface
{
    /**
     * @var array
     */
    private $buffer = [];

    /**
     * @param FetchedFile $file
     * @param string $uri
     * @param array $parameters
     */
    abstract protected function doStore(FetchedFile $file, $uri, array $parameters = []);

    /**
     * {@inheritdoc}
     */
    public function store(FetchedFile $file, $uri, array $parameters = [])
    {
        $this->buffer[] = [
            'file' => $file,
            'uri' => $uri,
            'parameters' => $parameters,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function processDeferred()
    {
        foreach ($this->buffer as $item) {
            $this->doStore($item['file'], $item['uri'], $item['parameters']);
        }

        $this->buffer = [];
    }
}