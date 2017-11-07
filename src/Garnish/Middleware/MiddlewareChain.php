<?php

namespace Creativestyle\Garnish\Middleware;


use Creativestyle\Garnish\Fetcher\FetchedFile;

class MiddlewareChain implements MiddlewareInterface
{
    /**
     * @var MiddlewareInterface[]
     */
    private $middlewares = [];

    /**
     * @param MiddlewareInterface $middleware
     * @return MiddlewareChain
     */
    public function addMiddleware(MiddlewareInterface $middleware)
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function process(FetchedFile $fetchedFile, array $parameters)
    {
        $result = $fetchedFile;

        foreach ($this->middlewares as $middleware) {
            $result = $middleware->process($result, $parameters);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function parameterNames()
    {
        $names = [];

        foreach ($this->middlewares as $middleware) {
            $names = array_merge($names, $middleware->parameterNames());
        }

        return array_unique($names);
    }
}