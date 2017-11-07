<?php

namespace Creativestyle\Garnish\Utils;


use Creativestyle\Garnish\Config\ConfigurationException;

class FilesystemUtils
{
    /**
     * @param string $dir
     * @param string $description
     * @throws ConfigurationException
     */
    public static function ensureDirectoryWritable($dir, $description)
    {
        if (!is_dir($dir)) {
            if (false !== mkdir($dir, 0770, true)) {
                return;
            }

            throw new ConfigurationException(sprintf('%s directory "%c" could not be created', ucfirst($description), $dir));
        }

        if (!is_writable($dir)) {
            throw new ConfigurationException(sprintf('%s directory "%c" is not writable', ucfirst($description), $dir));
        }
    }

    /**
     * @param string $path
     * @return string
     */
    public static function ensureTrailingSlash($path)
    {
        return rtrim($path, '/') . '/';
    }

    /**
     * @param string $a
     * @param string $b
     * @return string
     */
    public static function joinPath($a, $b)
    {
        return rtrim($a, '/') . '/' . ltrim($b, '/');
    }
}