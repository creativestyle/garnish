<?php

namespace Creativestyle\Garnish\Config;

use Doctrine\Common\Cache\Cache;

class AppConfig
{
    const SETTINGS_CACHE_KEY = 'garnish_settings';
    const SETTINGS_MTIME_CACHE_KEY = 'garnish_settings_mtime';

    /**
     * @var array
     */
    private static $defaultSettings = [
        'max_age' => 24 * 60 * 60,
        'max_lifetime' => '1 day',
        'storage_plugin' => 'filesystem',
        'user_agent' => 'Garnish/' . GARNISH_VERSION,
        'enable_x_sendfile' => false,
        'storage_directory' => '%root_dir%web/images/',
        'fetch_timeout' => 5,
        'restrict_referers' => false,
        'restrict_urls' => false,
        'log_fetch_errors' => false,
        'log_level' => 'warning',
        'url_parameter_name' => 'u',
        'default_parameter_name' => 'd',
        'middleware' => ['resize'],
    ];

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var array
     */
    private $settings;

    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var string
     */
    private $configFilename;

    /**
     * @param Cache $cache
     * @param string $configFilename
     * @param string $rootDir
     */
    public function __construct(
        Cache $cache,
        $configFilename,
        $rootDir
    ) {
        $this->cache = $cache;
        $this->rootDir = $rootDir;
        $this->configFilename = $configFilename;

        if (file_exists($configFilename)) {
            if (!is_readable($configFilename)) {
                throw new ConfigurationException(sprintf('Configuration file "%s" cannot be read.', $configFilename));
            }

            $this->setupConfigFile($configFilename);
        } else {
            $this->settings = self::$defaultSettings;
        }

        $this->interpolatePlaceholders();
    }

    private function interpolatePlaceholders()
    {
        foreach ($this->settings as $key => &$val) {
            if (is_string($val)) {
                $val = str_replace('%root_dir%', $this->rootDir, $val);
            }
        }
    }

    private function setupConfigFile($configFilename)
    {
        $fileMTime = filemtime($configFilename);
        $cacheMTime = $this->cache->fetch(self::SETTINGS_MTIME_CACHE_KEY);
        $settings = $this->cache->fetch(self::SETTINGS_CACHE_KEY);

        if (!$cacheMTime || !$fileMTime || $fileMTime > $cacheMTime || !$settings) {
            $settings = $this->readConfigFile($configFilename);
            $this->cache->save(self::SETTINGS_CACHE_KEY, $settings);
            $this->cache->save(self::SETTINGS_MTIME_CACHE_KEY, $fileMTime);
        }

        $this->settings = array_merge(self::$defaultSettings, $settings);
    }

    /**
     * @param string $filename
     * @return array
     */
    private function readConfigFile($filename)
    {
        $settings = json_decode(file_get_contents($filename), true);

        if (null === $settings) {
            throw new ConfigurationException(sprintf('Could not parse the configuration file "%s" with error "%s"',
                $filename, json_last_error_msg()));
        }

        return $settings;
    }

    /**
     * @return string
     */
    public function getConfigFilename()
    {
        return $this->configFilename;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function exists($key)
    {
        return array_key_exists($key, $this->settings);
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getRequired($key)
    {
        if (!$this->exists($key)) {
            throw new ConfigurationException(sprintf('Configuration is missing key "%s"', $key));
        }

        return $this->settings[$key];
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (!isset($this->settings[$key])) {
            return $default;
        }

        return $this->settings[$key];
    }

    /**
     * @return array
     */
    public function all()
    {
        return $this->settings;
    }
}
