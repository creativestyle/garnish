<?php

namespace Creativestyle\Garnish\Endpoint;

use Creativestyle\Garnish\Config\AppConfig;
use Creativestyle\Garnish\Exception\HttpBadRequestException;
use Creativestyle\Garnish\Exception\HttpForbiddenException;
use Creativestyle\Garnish\Exception\HttpNotFoundException;
use Creativestyle\Garnish\Fetcher\FileCouldNotBeFetchedException;
use Creativestyle\Garnish\Fetcher\FileFetcherInterface;
use Creativestyle\Garnish\Middleware\MiddlewareInterface;
use Creativestyle\Garnish\Storage\StorageInterface;
use Creativestyle\Garnish\Utils\ArrayUtils;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ProxyImageEndpoint implements EndpointInterface
{
    /**
     * @var AppConfig
     */
    private $config;

    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var FileFetcherInterface
     */
    private $fetcher;

    /**
     * @var MiddlewareInterface
     */
    private $middlewareChain;

    /**
     * @var
     */
    private $debugMode;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $restrictedReferers;

    /**
     * @var array
     */
    private $restrictedUrls;

    /**
     * @var bool
     */
    private $logFetchErrors;

    /**
     * @var string
     */
    private $urlParam;

    /**
     * @var string
     */
    private $defaultParam;

    /**
     * @param AppConfig $config
     * @param StorageInterface $storage
     * @param FileFetcherInterface $fetcher
     * @param MiddlewareInterface $middlewareChain
     * @param LoggerInterface $logger
     * @param bool $debugMode
     */
    public function __construct(
        AppConfig $config,
        StorageInterface $storage,
        FileFetcherInterface $fetcher,
        MiddlewareInterface $middlewareChain,
        LoggerInterface $logger,
        $debugMode
    ) {
        $this->config = $config;
        $this->storage = $storage;
        $this->fetcher = $fetcher;
        $this->middlewareChain = $middlewareChain;
        $this->debugMode = $debugMode;
        $this->logger = $logger;
        $this->restrictedReferers = $config->get('restrict_referers');
        $this->restrictedUrls = $config->get('restrict_urls');
        $this->logFetchErrors = $config->get('log_fetch_errors');
        $this->urlParam = $config->get('url_parameter_name');
        $this->defaultParam = $config->get('default_parameter_name');
    }

    /**
     * @param Response $response
     * @return Response
     */
    protected function processResponse(Response $response)
    {
        if ($response->getStatusCode() == '200') {
            $response->setPublic();
            $response->setMaxAge($this->config->getRequired('max_age'));
        }

        return $response;
    }

    protected function validateUrl($url)
    {
        /* Srsly need sth better than this...
         * Also how to prevent directory traversal if the directory does not yet exist.
         * We might not use filesystem storage too... */
        if (false === filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        if (!preg_match('/https?:\/\//', $url)) {
            return false;
        }

        if (false !== strpos($url, '/../')) {
            return false;
        }

        if (false !== strpos($url, '/.../')) {
            return false;
        }

        return true;
    }

    /**
     * Checks whether the referer is allowed.
     *
     * @param Request $request
     * @return bool
     */
    protected function checkReferrer(Request $request)
    {
        if (!$this->restrictedReferers) {
            return true;
        }

        if (!$request->headers->has('referer')) {
            return true;
        }

        $referer = $request->headers->get('referer');

        foreach ($this->restrictedReferers as $pattern) {
            if (preg_match('<' . $pattern . '>', $referer)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks whether the target url is allowed.
     *
     * @param string $url
     * @return bool
     */
    protected function checkUrl($url)
    {
        if (!$this->restrictedUrls) {
            return true;
        }

        foreach ($this->restrictedUrls as $pattern) {
            if (preg_match('<' . $pattern . '>', $url)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Performs security, sanity & validation checks on the request.
     *
     * @param Request $request
     */
    protected function checkRequest(Request $request)
    {
        if (!$this->checkReferrer($request)) {
            $this->logger->debug('[Proxy] Sending 403 because referer is not allowed');

            throw new HttpForbiddenException();
        }

        $url = $request->query->get($this->urlParam);

        if (!$this->checkUrl($url)) {
            $this->logger->debug('[Proxy] Sending 403 because url is not allowed');

            throw new HttpForbiddenException();
        }

        if (!$url) {
            throw new HttpBadRequestException('Missing "url" query parameter');
        }

        if ($this->validateUrl($url) === false) {
            throw new HttpBadRequestException('Invalid "url" supplied');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function handleRequest(Request $request)
    {
        $url = $request->query->get($this->urlParam);
        
        $parameters = ArrayUtils::pick($request->query->all(), $this->middlewareChain->parameterNames());

        if ($this->storage->exists($url, $parameters)) {
            $this->logger->debug('[Proxy] Storage plugin has {url}', ['url' => $url]);

            return $this->processResponse($this->storage->serve($url, $parameters));
        }

        try {
            $file = $this->fetcher->fetchFile($url);
        } catch (FileCouldNotBeFetchedException $exception) {
            if ($this->debugMode || $this->logFetchErrors) {
                $this->logger->warning('[Proxy] Could not fetch url {url}: {exception}', ['url' => $url, 'exception' => $exception->getMessage()]);
            }

            if ($request->query->has($this->defaultParam)) {
                return new RedirectResponse($request->query->get($this->defaultParam));
            }

            throw new HttpNotFoundException(
                'File could not be fetched from upstream server: '. $exception->getMessage()
            );
        }

        $file = $this->middlewareChain->process($file, $parameters);

        $this->storage->store($file, $url, $parameters);

        $response = new BinaryFileResponse($file->getFilename());

        $this->logger->debug('[Proxy] Directly sending file {file} for {url}', ['file' => $file->getFilename(), 'url' => $url]);

        if ($file->getContentType()) {
            $response->headers->set('Content-Type', $file->getContentType());
        }

        if ($this->debugMode && $file->getTotalTime()) {
            $response->headers->set('X-Garnish-Fetch-Time', number_format($file->getTotalTime(), 3) . 's');
        }

        $response->setAutoEtag();
        $response->setAutoLastModified();

        return $this->processResponse($response);
    }
}