<?php

namespace LireinCore\ImgCache;

trait TPixel
{
    /**
     * @param string $value
     * @param int $src_value
     * @return int
     */
    protected function getPxSize($value, $src_value)
    {
        if (strpos($value, 'px') !== false) $size = str_replace('px', '', $value);
        elseif (strpos($value, '%') !== false) $size = ((int) str_replace('%', '', $value)) * $src_value / 100;
        else $size = $value;

        return (int) $size;
    }

    /**
     * @param string $value
     * @param int $src_value
     * @param int $size
     * @return int
     */
    protected function getPxOffset($value, $src_value, $size)
    {
        if ($value == 'center') $offset = ($src_value - $size) / 2;
        elseif ($value == 'left' || $value == 'top') $offset = 0;
        elseif ($value == 'right' || $value == 'bottom') $offset = $src_value - $size;
        elseif (strpos($value, 'px') !== false) $offset = str_replace('px', '', $value);
        elseif (strpos($value, '%') !== false) $offset = ((int) str_replace('%', '', $value)) * $src_value / 100;
        else $offset = $value;

        return (int) $offset;
    }

    /**
     * @param string $value
     * @param int $src_value
     * @param int $size
     * @return int
     */
    protected function getWtOffset($value, $src_value, $size)
    {
        $offset = $this->getPxOffset($value, $src_value, $size);

        if ($offset + $size > $src_value) {
            $offset = $src_value - $size;
        }

        return $offset;
    }
}