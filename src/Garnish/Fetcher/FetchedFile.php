<?php

namespace Creativestyle\Garnish\Fetcher;


class FetchedFile
{
    /**
     * @var resource
     */
    private $filename;

    /**
     * @var int
     */
    private $responseCode;

    /**
     * @var string
     */
    private $effectiveUrl;

    /**
     * @var string
     */
    private $contentType;

    /**
     * @var int
     */
    private $contentLength;

    /**
     * Total time it took to download the file in sec.
     *
     * @var float
     */
    private $totalTime;

    /**
     * @param string $filename
     * @param int $responseCode
     * @param string $effectiveUrl
     * @param string $contentType
     * @param int $contentLength
     */
    public function __construct(
        $filename,
        $responseCode,
        $effectiveUrl,
        $contentType,
        $contentLength
    ) {
        $this->filename = $filename;
        $this->responseCode = $responseCode;
        $this->effectiveUrl = $effectiveUrl;
        $this->contentType = $contentType;
        $this->contentLength = $contentLength;
    }

    /**
     * Unlinks the underlying temp file.
     */
    public function unlink()
    {
        if ($this->isValid()) {
            unlink($this->filename);
        }
    }

    /**
     * @return int
     */
    public function getTotalTime()
    {
        return $this->totalTime;
    }

    /**
     * @param int $totalTime
     */
    public function setTotalTime($totalTime)
    {
        $this->totalTime = $totalTime;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return file_exists($this->filename) && is_readable($this->filename);
    }

    /**
     * @return resource
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @return int
     */
    public function getResponseCode()
    {
        return $this->responseCode;
    }

    /**
     * @return string
     */
    public function getEffectiveUrl()
    {
        return $this->effectiveUrl;
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * @return int
     */
    public function getContentLength()
    {
        return $this->contentLength;
    }
}