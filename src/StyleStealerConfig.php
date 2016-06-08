<?php
namespace OWOW\StyleStealer;

use OWOW\StyleStealer\Exception;

class StyleStealerConfig
{
    /**
     * The style record fields.
     *
     * @var array
     */
    private $styles = [];

    /**
     * Config bool for getting images.
     *
     * @var bool
     */
    private $images = false;

    /**
     * Config for getting specified meta tags.
     *
     * @var bool
     */
    private $metaTags = false;

    /**
     * bool that determine whether to check frameworks styles.
     *
     * @var bool
     */
    private $useFrameworks = false;

    /**
     * Which frameworks to avoid
     *
     * @var array
     */
    private $frameworksToAvoid = [];

    /**
     * Should we use Collections?
     *
     * @var bool
     */
    private $usingCollections = false;

    /**
     * @var mixed The raw config send through parameters.
     */
    private $rawConfig;

    public function __construct($config)
    {
        $this->initialize($config);
    }

    /**
     * Initialize the class from static call.
     *
     * @param $config
     * @return StyleStealerConfig
     */
    public static function create($config)
    {
        if ($config instanceof StyleStealerConfig) {
            return $config;
        }

        return new self($config);
    }

    /**
     * Handle the config. This means we're going to check if the config
     * exists or we need to create a new one. And how we need to create
     * a new one (from array, from config file or from
     * existing config class).
     *
     * @param $config
     * @return $this
     * @throws Exception\ConfigFailedException
     */
    private function initialize($config)
    {
        $this->rawConfig = $config; // Set a raw config.

        if ($config instanceof StyleStealerConfig) { // Is config instance of $this?
            return $config;
        }

        if (is_array($config)) { // Is config array?
            $this->createFromArray();
        }

        if (is_null($config)) {
            if (function_exists('config_path') && file_exists(config_path('style-stealer.php'))) {
                $this->createFromLaravelConfig();
            } else if (file_exists($this->configFilePath())) {
                $this->rawConfig = require $this->configFilePath();
                $this->createFromArray();
            } else {
                throw new Exception\ConfigFailedException('No config configuration found.');
            }
        }

        if (function_exists('collect')) { // Can we use Laravel Collection's?
            $this->usingCollections = true;
        }

        return $this;
    }

    /**
     * Create the config from an array.
     */
    private function createFromArray()
    {
        if (array_key_exists('record', $this->rawConfig) && array_key_exists('styles', $this->rawConfig['record'])) {
            $this->setStyles($this->rawConfig['record']['styles']);

            if (in_array('images', $this->rawConfig['record'])) {
                $this->setImageConfig(true);
            }

            if (array_key_exists('meta_tags', $this->rawConfig['record'])) {
                $this->metaTags = $this->rawConfig['record']['meta_tags'];
            }

            if (array_key_exists('frameworks', $this->rawConfig)) {
                $this->setFrameworks($this->rawConfig['frameworks']);
            }
        } else {
            throw new Exception\ConfigFailedException('Config missing proper record fields.');
        }
    }

    /**
     * Create the config from a Laravel Config file. This will first
     * import the config as an array than run the createFromArray method.
     *
     * @throws Exception\ConfigFailedException
     */
    private function createFromLaravelConfig()
    {
        $configPath = config_path('style-stealer.php');

        if (file_exists($configPath)) {
            $this->rawConfig = require $configPath;
            $this->createFromArray();
        } else {
            throw new Exception\ConfigFailedException("Config file '{$configPath}' not found.");
        }

    }

    /**
     * Check if config has a specific field.
     *
     * @param $field
     * @return bool
     */
    public function has($field)
    {
        try {
            $configFound = $this->checkConfigFields($field);
        } catch (Exception\ConfigFailedException $e) {
            throw new $e;
        }

        return $configFound;
    }

    /**
     * Get the config record styles.
     *
     * @param $field
     * @return bool
     */
    private function checkConfigFields($field)
    {
        $styles = $this->getStyles();

        if (in_array($field, $styles)) {
            return true;
        }

        if ($field === 'styles' && array_key_exists('styles', $this->rawConfig['record'])) {
            return true;
        }

        if ($field === 'images' && $this->getImageConfig()) {
            return true;
        }

        if ($field === 'frameworks') {
            return $this->useFrameworks;
        }

        return false;
    }

    public function setFrameworks($useFrameworks, $frameworksToAvoid = null)
    {
        $this->useFrameworks = $useFrameworks;

        if (is_null($frameworksToAvoid)) {
            $this->frameworksToAvoid = $this->rawConfig['known_frameworks'];
        } else {
            $this->frameworksToAvoid = $frameworksToAvoid;
        }
    }

    /**
     * @return bool|array
     */
    public function getFrameworks()
    {
        return $this->frameworksToAvoid;
    }

    /**
     * Set styles to get from requests.
     *
     * @param $styles
     */
    public function setStyles($styles)
    {
        $this->styles = $styles;
    }

    /**
     * Get style config.
     *
     * @return array
     */
    public function getStyles()
    {
        return $this->styles;
    }

    /**
     * Set images to get from requests.
     *
     * @param $imageConfig bool
     */
    public function setImageConfig($imageConfig)
    {
        $this->images = $imageConfig;
    }

    /**
     * Get images config.
     *
     * @return bool
     */
    public function getImageConfig()
    {
        return $this->images;
    }

    /**
     * Config for skipping white/black colors.
     * 
     * @return bool
     */
    public function skipColors()
    {
        if (array_key_exists('skip_greyscale', $this->rawConfig)) {
            return $this->rawConfig['skip_greyscale'];
        }

        return false;
    }

    /**
     * Get the default configuration file.
     *
     * @param string $DS
     * @return string
     */
    private function configFilePath($DS = '/')
    {
        return __DIR__ . "{$DS}..{$DS}recourses{$DS}config{$DS}style-stealer.php";
    }
}