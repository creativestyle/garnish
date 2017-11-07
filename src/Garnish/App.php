<?php

namespace Creativestyle\Garnish;


use Creativestyle\Garnish\Config\AppConfig;
use Creativestyle\Garnish\Endpoint\EndpointInterface;
use Creativestyle\Garnish\Exception\HttpResponseException;
use Creativestyle\Garnish\Fetcher\FileFetcherInterface;
use Creativestyle\Garnish\Service\ContainerFactory;
use Creativestyle\Garnish\Service\ContainerInterface;
use Creativestyle\Garnish\Storage\DeferredStorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Debug\Debug;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

define('GARNISH_VERSION', '0.1.0');

class App
{
    /**
     * @var EndpointInterface
     */
    private $endpoint;

    /**
     * @var ContainerInterface
     */
    private $serviceContainer;

    /**
     * @var AppConfig
     */
    private $config;

    /**
     * @var bool
     */
    private $debugMode;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Registers available endpoints.
     *
     * @param bool $debugMode
     */
    public function __construct($debugMode = false)
    {
        $this->debugMode = $debugMode;

        if ($debugMode) {
            Debug::enable();
        }

        $this->serviceContainer = ContainerFactory::create(
            $this->getRootDir(),
            $this->getCacheDir(),
            $this->getLogDir(),
            $this->getConfigFilename(),
            $debugMode
        );

        $this->config = $this->serviceContainer->get('config');
        $this->logger = $this->serviceContainer->get('logger');

        if ($this->config->getRequired('enable_x_sendfile')) {
            $this->logger->debug('X-Sendfile support is enabled.');

            BinaryFileResponse::trustXSendfileTypeHeader();
        }

        $this->endpoint = $this->serviceContainer->get('endpoint.proxy_image');
    }

    /**
     * @return string
     */
    private function getLogDir()
    {
        return $this->getRootDir() . 'var/log/';
    }

    /**
     * @return string
     */
    private function getCacheDir()
    {
        return $this->getRootDir() . 'var/cache/';
    }

    /**
     * @return string
     */
    private function getConfigFilename()
    {
        return $this->getRootDir() . 'config.json';
    }


    /**
     * @return string
     */
    private function getRootDir()
    {
        return realpath(__DIR__ . '/../..') . '/';
    }

    /**
     * @return ContainerInterface
     */
    public function getServiceContainer()
    {
        return $this->serviceContainer;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function handleRequest(Request $request)
    {
        try {
            $response = $this->endpoint->handleRequest($request);
        } catch (HttpResponseException $exception) {
            $response = new Response($exception->getMessage(), $exception->getCode());
        }

        $response->prepare($request);

        if ($this->debugMode) {
            $response->headers->add([
                'X-Garnish' => GARNISH_VERSION,
                'X-Garnish-Peak-Mem' => sprintf('%.2fMiB', memory_get_peak_usage() / 0x100000),
            ]);
        }

        return $response;
    }

    /**
     * Processes long running tasks after the response has been sent.
     */
    public function processDeferred()
    {
        $storage = $this->serviceContainer->get('storage');

        if ($storage instanceof DeferredStorageInterface) {
            $storage->processDeferred();
        }

        /** @var FileFetcherInterface $fetcher */
        $fetcher = $this->serviceContainer->get('file_fetcher');
        $fetcher->cleanup();
    }
}
