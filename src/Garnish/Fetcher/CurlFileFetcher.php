<?php

namespace Creativestyle\Garnish\Fetcher;

use Creativestyle\Garnish\Utils\FilesystemUtils;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;

class CurlFileFetcher implements FileFetcherInterface
{
    /**
     * @var string
     */
    private $userAgent;

    /**
     * @var string
     */
    private $cacheDirectory;

    /**
     * @var int
     */
    private $fetchTimeout;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param string $cacheDirectory
     * @param string $userAgent
     * @param int $fetchTimeout Timeout in seconds
     * @param LoggerInterface $logger
     */
    public function __construct($cacheDirectory, $userAgent, $fetchTimeout, LoggerInterface $logger)
    {
        if (!extension_loaded('curl')) {
            throw new \RuntimeException('cURL is required but not installed.');
        }

        $this->userAgent = $userAgent;
        $this->cacheDirectory = FilesystemUtils::joinPath($cacheDirectory, 'fetcher/');
        $this->fetchTimeout = $fetchTimeout;
        $this->logger = $logger;

        FilesystemUtils::ensureDirectoryWritable($this->cacheDirectory, 'fetcher cache');
    }

    /**
     * @param string $uri
     * @return FetchedFile
     */
    public function fetchFile($uri)
    {
        $filename = $this->cacheDirectory . md5($uri) . rand(1111, 9999);
        $ch = curl_init($uri);
        $fh = fopen($filename, 'w+');

        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->fetchTimeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_FILE, $fh);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);

        if (false === curl_exec($ch)) {
            throw new FileCouldNotBeFetchedException(
                $uri,
                curl_getinfo($ch, CURLINFO_HTTP_CODE),
                curl_strerror(curl_errno($ch))
            );
        }

        $file = new FetchedFile(
            $filename,
            curl_getinfo($ch, CURLINFO_HTTP_CODE),
            curl_getinfo($ch, CURLINFO_EFFECTIVE_URL),
            curl_getinfo($ch, CURLINFO_CONTENT_TYPE),
            curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD)
        );

        $file->setTotalTime(curl_getinfo($ch, CURLINFO_TOTAL_TIME));

        curl_close($ch);
        fclose($fh);

        return $file;
    }

    /**
     * {@inheritdoc}
     */
    public function cleanup()
    {
        $finder = new Finder();
        $finder->files()->in($this->cacheDirectory)->date('before 1 minute ago');

        /** @var \SplFileInfo $file */
        foreach ($finder as $file) {
            $path = $file->getRealPath();

            if (file_exists($path)) {
                $this->logger->debug('[Fetcher] Removing stale file {file} from fetcher cache', ['file' => $path]);

                unlink($path);
            }
        }
    }
}