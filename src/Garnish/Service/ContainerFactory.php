<?php

namespace Creativestyle\Garnish\Service;

use Creativestyle\Garnish\Config\AppConfig;
use Creativestyle\Garnish\Config\ConfigurationException;
use Creativestyle\Garnish\Console\DeleteExpiredCommand;
use Creativestyle\Garnish\Console\DumpConfigCommand;
use Creativestyle\Garnish\Endpoint\ProxyImageEndpoint;
use Creativestyle\Garnish\Fetcher\CurlFileFetcher;
use Creativestyle\Garnish\Log\SimpleFileLogger;
use Creativestyle\Garnish\Middleware\MiddlewareChain;
use Creativestyle\Garnish\Middleware\ResizeMiddleware;
use Creativestyle\Garnish\Storage\FilesystemStorage;
use Creativestyle\Garnish\Utils\FilesystemUtils;
use Doctrine\Common\Cache\FilesystemCache;
use Psr\Log\LogLevel;
use Imagine;

class ContainerFactory
{
    /**
     * @param string $rootDir
     * @param string $cacheDir
     * @param string $logDir
     * @param string $configFilename
     * @param bool $debugMode
     * @return ContainerInterface
     */
    public static function create(
        $rootDir,
        $cacheDir,
        $logDir,
        $configFilename,
        $debugMode = false
    ) {
        $c = new Container([
            '_root_dir' => $rootDir,
            '_debug_mode' => $debugMode,
            '_cache_directory' => $cacheDir,
            '_log_directory' => $logDir,
            '_config_filename' => $configFilename
        ]);

        $c['_filesystem_plugin'] = function ($c) {
            return $c['config']->getRequired('filesystem_plugin');
        };

        $c['_storage_plugin'] = function ($c) {
            return $c['config']->getRequired('storage_plugin');
        };

        $c['_storage_directory'] = function ($c) {
            return $c['config']->getRequired('storage_directory');
        };

        $c['_user_agent'] = function ($c) {
            return $c['config']->getRequired('user_agent');
        };

        $c['_fetch_timeout'] = function ($c) {
            return $c['config']->getRequired('fetch_timeout');
        };

        $c['_oldest_mtime'] = function ($c) {
            return time() - (int)$c['config']->getRequired('max_age');
        };

        $c['_middleware_list'] = function ($c) {
            return $c['config']->getRequired('middleware');
        };

        $c['cache'] = function ($c) {
            return new FilesystemCache($c['_cache_directory']);
        };

        $c['config'] = function ($c) {
            return new AppConfig($c['cache'], $c['_config_filename'], $c['_root_dir']);
        };

        $c['endpoint.proxy_image'] = function ($c) {
            return new ProxyImageEndpoint($c['config'], $c['storage'], $c['file_fetcher'], $c['middleware.chain'], $c['logger'], $c['_debug_mode']);
        };

        $c['storage.filesystem'] = function ($c) {
            return new FilesystemStorage($c['_storage_directory'], $c['_oldest_mtime'], $c['logger']);
        };

        $c['middleware.resize'] = function ($c) {
            return new ResizeMiddleware($c['imagine']);
        };

        $c['middleware.chain'] = function ($c) {
            $chain = new MiddlewareChain();

            foreach ($c['_middleware_list'] as $name) {
                $serviceName = sprintf('middleware.%s', $name);

                if (!$c->exists($serviceName)) {
                    throw new ConfigurationException(sprintf('Middleware plugin "%s" does not exist.', $name));
                }

                $chain->addMiddleware($c->get($serviceName));
            }

            return $chain;
        };


        $c['file_fetcher'] = function ($c) {
            return new CurlFileFetcher($c['_cache_directory'], $c['_user_agent'], $c['_fetch_timeout'], $c['logger']);
        };

        $c['logger'] = function ($c) {
            $debug = $c['_debug_mode'];

            return new SimpleFileLogger(
                FilesystemUtils::joinPath($c['_log_directory'], $debug ? 'garnish.debug.log' : 'garnish.log'),
                $debug ? LogLevel::DEBUG : $c['config']->getRequired('log_level')
            );
        };

        $c['storage'] = function ($c) {
            $serviceName = sprintf('storage.%s', $c['_storage_plugin']);

            if (!isset($c[$serviceName])) {
                throw new ConfigurationException(sprintf('Storage plugin service "%s" does not exist', $serviceName));
            }

            return $c[$serviceName];
        };

        $c['imagine'] = function ($c) {
            if (extension_loaded('gmagick')) {
                return new Imagine\Gmagick\Imagine();
            }

            if (extension_loaded('imagick')) {
                return new Imagine\Imagick\Imagine();
            }

            if (extension_loaded('gd')) {
                return new Imagine\Gd\Imagine();
            }

            throw new ConfigurationException('No image manipulation library was found :(');
        };

        $c['command.delete_expired'] = function ($c) {
            return new DeleteExpiredCommand($c['config'], $c['storage']);
        };

        $c['command.config_dump'] = function ($c) {
            return new DumpConfigCommand($c['config']);
        };

        return $c;
    }
}