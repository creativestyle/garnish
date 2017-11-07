<?php

namespace Creativestyle\Garnish\Service;


interface ContainerInterface
{
    /**
     * @param string $name
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function get($name);

    /**
     * @param string $name
     * @return mixed
     */
    public function exists($name);

    /**
     * Returns all services with keys prefixed with "${type}."
     *
     * @param string $name
     * @return array
     */
    public function getAllOfType($name);
}