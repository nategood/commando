<?php

namespace Commando\Util;

class Terminal
{
    /**
     * Width of current terminal window
     * On Unixy systems, relies on tput.  Falls back to a default value of
     * $default
     *
     * @param int $default
     * @return int
     */
    public static function getWidth($default = 80)
    {
        $cols = @exec('tputs cols 2>/dev/null');
        return !empty($cols) ? $cols : $default;
    }

    /**
     * Height of current terminal window
     * @see getWidth
     * @param int $default
     * @return int
     */
    public static function getHeight($default = 32)
    {
        $lines = @exec('tputs lines 2>/dev/null');
        return !empty($lines) ? $lines : $default;
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
     * @return string
     */
    public static function pad($text, $width, $pad = ' ', $mode = STR_PAD_RIGHT)
    {
        $width = strlen($text) - mb_strlen($text, 'UTF-8') + $width;
        return str_pad($text, $width, $pad, $mode);
    }
}