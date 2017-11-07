<?php

namespace Creativestyle\Garnish\Storage;


use Creativestyle\Garnish\Config\ConfigurationException;
use Creativestyle\Garnish\Fetcher\FetchedFile;
use Creativestyle\Garnish\Utils\FilesystemUtils;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FilesystemStorage extends AbstractDeferredStorage
{
    /**
     * @var
     */
    private $storageDirectory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var int
     */
    private $oldestMtime;

    /**
     * @param string $storageDirectory
     * @param int $oldestMtime Max age in seconds.
     * @param LoggerInterface $logger
     */
    public function __construct($storageDirectory, $oldestMtime, LoggerInterface $logger)
    {
        FilesystemUtils::ensureDirectoryWritable($storageDirectory, 'Filesystem storage cache');

        $this->storageDirectory = FilesystemUtils::ensureTrailingSlash($storageDirectory);
        $this->logger = $logger;
        $this->oldestMtime = $oldestMtime;
    }

    /**
     * @param string $uri
     * @param array $parameters
     * @return string
     */
    private function getFilename($uri, array $parameters = [])
    {
        ksort($parameters);

        $id = md5('[' . $uri . ']' . http_build_query($parameters));

        return $this->storageDirectory . $id[0] . '/' . $id[1] . '/' . $id[2] . '/' . $id;
    }

    /**
     * {@inheritdoc}
     */
    protected function doStore(FetchedFile $file, $uri, array $parameters = [])
    {
        $filename = $this->getFilename($uri, $parameters);
        $dirname = dirname($filename);

        if (!is_dir($dirname)) {
            mkdir($dirname, 0770, true);
        }

        copy($file->getFilename(), $filename);

        $this->logger->debug('[Filesystem] Storing {uri} into {file}', ['uri' => $uri, 'file' => $filename]);
    }

    /**
     * {@inheritdoc}
     */
    public function serve($uri, array $parameters = [])
    {
        $filename = $this->getFilename($uri, $parameters);
        $response = new BinaryFileResponse($filename);

        $response->setAutoLastModified();
        $response->setAutoEtag();

        $this->logger->debug('[Filesystem] Serving {uri} from {file}', ['uri' => $uri, 'file' => $filename]);

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function exists($uri, array $parameters = [])
    {
        $filename = $this->getFilename($uri, $parameters);

        if (!file_exists($filename)) {
            return false;
        }

        return filemtime($filename) >= $this->oldestMtime;
    }

    /**
     * Removes empty directory that was holding the image(s)
     *
     * @param string $path
     */
    private function removePath($path)
    {
        $dir = dirname($path);

        if (substr($dir, 0, strlen($this->storageDirectory)) !== $this->storageDirectory) {
            return;
        }

        $finder = new Finder();

        if (iterator_count($finder->in($dir)) === 0) {
            rmdir($dir);

            $this->removePath($dir);
            return;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function cleanup(\DateTime $since)
    {
        $finder = new Finder();
        $finder->files()->in($this->storageDirectory)->date('before ' . $since->format(DATE_ISO8601));

        $c = 0;

        /** @var \SplFileInfo $file */
        foreach ($finder as $file) {
            $path = $file->getRealPath();

            if (file_exists($path)) {
                $this->logger->debug('[Filesystem] Removing stale file {file} from storage cache', ['file' => $path]);

                unlink($path);
                $c++;

                $this->removePath($path);
            }
        }

        return $c;
    }
}