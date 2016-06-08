<?php
namespace OWOW\StyleStealer;

use Sabberworm\CSS\Value\Color;

class StyleStealerColors
{
    private $colorsToSkip = [];

    private $shouldSkip;

    /**
     * StyleStealerColors constructor.
     *
     * @param bool $shouldSkip
     */
    public function __construct($shouldSkip = false)
    {
        $this->shouldSkip = $shouldSkip;
    }

    public function shouldSkip($color)
    {
        if (!$this->shouldSkip) {
            return false;
        }

        $distances = array();
        $val = $this->hexToRGB($color);
        foreach ($this->getColorList() as $name => $c) {
            $distances[$name] = $this->distancel2($c, $val);
        }

        $mincolor = "";
        $minval = pow(2, 30); /*big value*/
        foreach ($distances as $k => $v) {
            if ($v < $minval) {
                $minval = $v;
                $mincolor = $k;
            }
        }
        
        $greyscales = ['white', 'black', 'gray'];
        
        return in_array($mincolor, $greyscales);
    }

    private function distancel2(array $color1, array $color2) {
        return sqrt(pow($color1[0] - $color2[0], 2) +
            pow($color1[1] - $color2[1], 2) +
            pow($color1[2] - $color2[2], 2));
    }

    /**
     * Set the colors to skip.
     *
     * @param array $colors
     */
    public function setColorsToSkip($colors)
    {
        $this->colorsToSkip = $colors;
    }

    /**
     * Make a HEX from a Color object.
     * 
     * @param Color $color
     * @return string
     */
    public static function colorToHex(Color $color)
    {
        $hex = '#';
        $index = 0;

        foreach ($color->getColor() as $size) {
            $hex .= str_pad(dechex(intval($size->getSize())), 2, "0", STR_PAD_LEFT);

            if (++$index == 3) break;
        }

        return $hex;
    }

    /**
     * TODO
     */
    public static function rgbToHex()
    {
    }

    /**
     * Convert HEX to RGB.
     *
     * @param $color
     * @return array|bool
     */
    function hexToRGB($color)
    {
        if ($color[0] == '#') {
            $color = substr($color, 1);
        }
        
        if (strlen($color) == 6) {
            list($r, $g, $b) = [$color[0] . $color[1],
                $color[2] . $color[3],
                $color[4] . $color[5]];
        } elseif (strlen($color) == 3) {
            list($r, $g, $b) = [$color[0] . $color[0],
                $color[1] . $color[1], $color[2] . $color[2]];
        } else {
            return false;
        }

        return [hexdec($r), hexdec($g), hexdec($b)];
    }

    private function getColorList()
    {
        return [
            "black" => array(0, 0, 0),
            "green" => array(0, 128, 0),
            "silver" => array(192, 192, 192),
            "lime" => array(0, 255, 0),
            "gray" => array(128, 0, 128),
            "olive" => array(128, 128, 0),
            "white" => array(255, 255, 255),
            "yellow" => array(255, 255, 0),
            "maroon" => array(128, 0, 0),
            "navy" => array(0, 0, 128),
            "red" => array(255, 0, 0),
            "blue" => array(0, 0, 255),
            "purple" => array(128, 0, 128),
            "teal" => array(0, 128, 128),
            "fuchsia" => array(255, 0, 255),
            "aqua" => array(0, 255, 255),
        ];
    }
}