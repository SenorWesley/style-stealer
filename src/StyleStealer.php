<?php
namespace OWOW\StyleStealer;

use DOMDocument;
use Sabberworm\CSS\Parser;
use Sabberworm\CSS\Rule\Rule;
use Sabberworm\CSS\Value\Color;
use Sabberworm\CSS\Value\CSSString;
use Sabberworm\CSS\Value\RuleValueList;
use Sabberworm\CSS\RuleSet\DeclarationBlock;

class StyleStealer
{
    /**
     * @var StyleStealerConfig instance.
     */
    private $config;

    /**
     * @var DOMDocument $html
     */
    private $html;

    /**
     * The color handler.
     * 
     * @var StyleStealerColors
     */
    private $color;

    /**
     * @var array
     */
    private $loadedStyles = [];

    /**
     * StyleStealer constructor.
     *
     * The HTML File can either be a DOM Document or an URL to a
     * web page which we than will get the DOM from.
     *
     * @param string|DOMDocument $htmlFile
     * @param array $config
     */
    public function __construct($htmlFile, $config = null)
    {
        $this->initialize(static::url($htmlFile), $config);
    }

    /**
     * Handle class initializing.
     *
     * @param $htmlFile
     * @param $config
     * @return $this
     */
    private function initialize($htmlFile, $config)
    {
        $this->handleHTML($htmlFile);
        $this->handleConfig($config);
        $this->createColorHandler();

        $this->startStealer();

        return $this;
    }

    /**
     * Handle the HTML file. Set the DOMDocument or create a new one.
     *
     * @param $htmlFile
     */
    public function handleHTML($htmlFile)
    {
        if ($htmlFile instanceof DOMDocument) {
            $this->html = $htmlFile;
        }

        $siteContent = file_get_contents($htmlFile); // Get content from URL.

        // Create a new DOM Document and load the HTML.
        $html = new DOMDocument();
        @$html->loadHTML($siteContent);

        $this->html = $html;
    }

    /**
     * Handle the config. This means we're going to check if the config
     * exists or we need to create a new one. And how we need to create
     * a new one (from array, from config file or from
     * existing config class).
     *
     * @param $config mixed
     * @return $this
     */
    public function handleConfig($config)
    {
        $this->config = StyleStealerConfig::create($config);

        return $this;
    }

    public function createColorHandler()
    {
        $this->color = new StyleStealerColors($this->config->skipColors());
    }

    /**
     * Set all the styles.
     */
    public function startStealer()
    {
        if ($this->config->has('images')) {
            $this->stealImages();
        }

        if ($this->config->has('styles')) {
            $this->stealStyles();
        }
    }

    public function steal()
    {
        // TODO: Get all data on init??
    }

    /**
     * Steal images from the DOM.
     */
    private function stealImages()
    {
        $this->loadedStyles['images'] = $this->getElementValues('img', 'src');
    }

    /**
     * TODO
     */
    private function stealStyles()
    {
        $this->loadedStyles['stylesheets'] = $files = $this->getElementValues('link', 'href', 'rel', 'stylesheet');

        $items = [];
        $styles = $this->config->getStyles();
        foreach ($styles as $style) {
            $items[$style] = [];
        }

        foreach ($files as $file => $used) {
            if ($this->shouldSkipStyleSheet($file)) continue;

            try {
                $content = file_get_contents(static::url($file));
            } catch (\Exception $e) {
                continue;
            }

            $this->updateStyleArray($items, $content, $styles);
        }

        /** @var \DOMElement $rawStyle */
        foreach ($this->getTags('style') as $rawStyle) {
            $this->updateStyleArray($items, $rawStyle->nodeValue[0]);
        }

        $this->loadedStyles['styles'] = $items;
    }

    /**
     * Update the array which contains all the styles.
     *
     * @param $items
     * @param string $rawCss
     * @param array $styles
     * @return array
     */
    private function updateStyleArray(&$items, $rawCss, $styles = null)
    {
        if (is_null($styles)) {
            $styles = $this->config->getStyles();
        }

        try {
            $css = (new Parser($rawCss))->parse();
        } catch (\Exception $e) {
            return false;
        }

        /** @var DeclarationBlock $block */
        foreach ($css->getAllDeclarationBlocks() as $block) { // Foreach CSS block.
            /** @var Rule $rule */
            foreach ($block->getRules() as $rule) { // Foreach CSS block rule.
                // Check if the rule has one of our styles.
                if (in_array($rule->getRule(), $styles)) {
                    $value = $this->getRuleValue($rule);

                    if (array_key_exists($value, $items[$rule->getRule()])) {
                        $items[$rule->getRule()][$value] += 1;
                    } else {
                        $items[$rule->getRule()][$value] = 1;
                    }
                }
            }
        }

        return $items;
    }

    /**
     * Get the element values of DOM Elements (tags).
     *
     * We can check for specific fields values. But with the
     * parameters on default, we won't.
     *
     * @param $tag
     * @param $attribute
     * @param null $type
     * @param null $typeValue
     * @return array
     */
    private function getElementValues($tag, $attribute, $type = null, $typeValue = null)
    {
        $values = [];

        /** @var \DOMElement $element */
        foreach ($this->getTags($tag) as $element) {
            if (!$element->hasAttribute($attribute)) {
                continue;
            }

            if (!is_null($type) && $element->getAttribute($type) != $typeValue) {
                continue;
            }

            $atrValue = $element->getAttribute($attribute);

            if (array_key_exists($atrValue, $values)) {
                $values[$atrValue] = $values[$atrValue] + 1;
            } else {
                $values[$atrValue] = 1;
            }
        }

        return $values;
    }

    /**
     * Get the Rule value.
     *
     * @param $value
     * @return null|string
     */
    private function getRuleValue($value)
    {
        if ($value instanceof Rule) {
            $rawValue = $value->getValue();
        } else {
            $rawValue = $value;
        }

        if (is_string($rawValue)) {
            return $rawValue;
        }

        if ($rawValue instanceof CSSString) {
            return $rawValue->getString();
        }

        if ($rawValue instanceof RuleValueList) {
            return $this->getRuleValue($rawValue->getListComponents()[0]);
        }

        if ($rawValue instanceof Color) {
            $hexColor = StyleStealerColors::colorToHex($rawValue);

            if (!$this->color->shouldSkip($hexColor)) {
                return StyleStealerColors::colorToHex($rawValue);
            }
        }

        return 'unknown';
    }

    /**
     * Check if we should skip the current stylesheet. This is can be
     * configured in config file.
     *
     * @param $file
     * @return bool
     */
    public function shouldSkipStyleSheet($file)
    {
        $shouldContinue = false;

        if (!$this->config->has('frameworks')) {
            foreach ($this->config->getFrameworks() as $framework) {
                if (strpos(strtolower($file), strtolower($framework)) !== false) {
                    $shouldContinue = true;
                }
            }
        }

        return $shouldContinue;
    }

    /**
     * Get DOM node list from DOM Document
     *
     * @param $name
     * @return \DOMNodeList
     */
    public function getTags($name)
    {
        return $this->html->getElementsByTagName($name);
    }

    /**
     * Get a specified style.
     *
     * @param $style
     * @param bool $sort
     * @return null|DOMList
     */
    public function get($style, $sort = true)
    {
        $items = [];
        $loadedStyles = $this->loadedStyles;

        if (array_key_exists($style, $loadedStyles)) {
            $items = $this->loadedStyles[$style];
        }

        if (array_key_exists('styles', $loadedStyles) && array_key_exists($style, $loadedStyles['styles'])) {
            $items = $loadedStyles['styles'][$style];
        }

        if ($sort && count($items)) {
            arsort($items);
        }

        return $items;
    }

    /**
     * Generate a URL which can be used by file_get_contents().
     *
     * @param $url
     * @param string $scheme
     * @return string
     */
    public static function url($url, $scheme = 'http')
    {
        if (substr($url, 0, 2) === "//") {
            return "{$scheme}:{$url}";
        }

        if (is_null(parse_url($url, PHP_URL_SCHEME))) {
            return "{$scheme}://{$url}";
        }

        return $url;
    }
}