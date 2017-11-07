<?php

namespace Creativestyle\Garnish\Service;

use Pimple\Container as BaseContainer;

class Container extends BaseContainer implements ContainerInterface
{
    /**
     * {@inheritdoc}
     */
    public function get($name)
    {
        return $this->offsetGet($name);
    }

    /**
     * {@inheritdoc}
     */
    public function exists($name)
    {
        return $this->offsetExists($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getAllOfType($name)
    {
        $result = [];
        $prefix = $name . '.';

        foreach ($this->keys() as $key) {
            if (substr($key, 0, strlen($prefix)) === $prefix) {
                $result[$key] = $this->get($key);
            }
        }

        return $result;
    }
}