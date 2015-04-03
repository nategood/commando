<?php

namespace Commando\Util;

class Terminal
{
    /**
     * Width of current terminal window
     * On Linux/Mac flavor systems, will use tput.  Falls back to a
     * default value of $default.  On Windows, will always fall back
     * to default.
     *
     * @param int $default
     * @return int
     */
    public static function getWidth($default = 80)
    {
        return self::tput($default, 'cols');
    }

    /**
     * Height of current terminal window
     * @see getWidth
     * @param int $default
     * @return int
     */
    public static function getHeight($default = 32)
    {
        return self::tput($default, 'lines');
    }

    /**
     * Make that terminal beep
     * Ask and ye shall receive
     * https://twitter.com/philsturgeon/status/240825183487791104
     */
    public static function beep()
    {
        echo "\x7";
    }

    /**
     * Sadly if you attempt to redirect stderr, e.g. "tput cols 2>/dev/null"
     * tput does not return the expected values.  As a result, to prevent tput
     * from writing to stderr, we first check the exit code and call it again
     * to get the value :-(.
     * @param int $default
     * @param string $param
     * @return int
     */
    private static function tput($default, $param = 'cols')
    {
        $test = exec('tput ' . $param . ' 2>/dev/null');
        if (empty($test))
            return $default;
        $result = intval(exec('tput ' . $param));
        return empty($result) ? $default : $result;
    }

    /**
     * Wrap text for printing
     * @param string $text
     * @param int $left_margin
     * @param int $right_margin
     * @param int $width attempts to use current terminal width by default
     * @return string
     */
    public static function wrap($text, $left_margin = 0, $right_margin = 0,
        $width = null)
    {
        if (empty($width)) {
            $width = self::getWidth();
        }
        $width = $width - abs($left_margin) - abs($right_margin);
        $margin = str_repeat(' ', $left_margin);
        return $margin . wordwrap($text, $width, PHP_EOL . $margin);
    }

    /**
     * @param string $text
     * @param int $width defaults to terminal width
     * @return string
     */
    public static function header($text, $width = null)
    {
        if (empty($width)) {
            $width = self::getWidth();
        }
        return self::pad($text, $width);
    }

    /**
     * A UT8 compatible string pad
     *
     * @param string $text
     * @param int    $width
     * @param string $pad
     * @param int    $mode
     *
     * @return string
     */
    public static function pad($text, $width, $pad = ' ', $mode = STR_PAD_RIGHT)
    {
        $width = strlen($text) - mb_strlen($text, 'UTF-8') + $width;
        return str_pad($text, $width, $pad, $mode);
    }
}