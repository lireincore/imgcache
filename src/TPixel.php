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

    /**
     * @param string $color
     * @return string|array
     */
    protected function parseColor($color)
    {
        if (false === strpos('#', $color)) {
            $arr = explode(',', $color);
            $count = count($arr);
            if ($count == 3 || $count == 4) {
                $result = [
                    0 => trim($arr[0]),
                    1 => trim($arr[1]),
                    2 => trim($arr[2])
                ];
                if ($count == 4) $result[3] = trim($arr[3]);
            } else return $color;
        } else {
            $result = $color;
        }

        return $result;
    }
}