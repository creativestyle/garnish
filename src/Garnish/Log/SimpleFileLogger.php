<?php

namespace Creativestyle\Garnish\Log;


use Creativestyle\Garnish\Utils\FilesystemUtils;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

class SimpleFileLogger extends AbstractLogger
{
    public static $logLevels = [
        LogLevel::EMERGENCY     => 0,
        LogLevel::ALERT         => 1,
        LogLevel::CRITICAL      => 2,
        LogLevel::ERROR         => 3,
        LogLevel::WARNING       => 4,
        LogLevel::NOTICE        => 5,
        LogLevel::INFO          => 6,
        LogLevel::DEBUG         => 7,
    ];

    /**
     * @var string
     */
    private $filename;

    /**
     * @var int
     */
    private $maxLevel;

    /**
     * @var resource
     */
    private $fh;

    /**
     * @var int
     */
    private $i = 0;

    /**
     * @param string $filename
     * @param string $maxLevel
     */
    public function __construct($filename, $maxLevel = LogLevel::ERROR)
    {
        FilesystemUtils::ensureDirectoryWritable(dirname($filename), 'log');

        $this->filename = $filename;
        $this->maxLevel = static::$logLevels[$maxLevel];

        $this->fh = fopen($this->filename, 'a');
    }

    public function __destruct()
    {
        fflush($this->fh);
        fclose($this->fh);
    }

    /**
     * @param string $message
     * @param array $context
     * @return string
     */
    private function interpolate($message, array $context = [])
    {
        if (empty($context)) {
            return $message;
        }

        $replace = [];

        foreach ($context as $key => $val) {
            $replace['{' . $key . '}'] = $val;
        }

        return strtr($message, $replace);
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = [])
    {
        if (static::$logLevels[$level] > $this->maxLevel) {
            return;
        }

        fwrite($this->fh,
            sprintf('[%s] %s: %s' . "\n", date('Y:m:d H:i:s'), strtoupper($level), $this->interpolate($message, $context))
        );

        if ($this->i % 10 == 0) {
            fflush($this->fh);
        }
    }
}