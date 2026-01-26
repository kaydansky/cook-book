<?php

namespace Cookbook\Helpers;

/**
 * Description of FormatDate
 *
 * @author AlexK
 */
class Format
{
    static function date($date)
    {
        $d = date_create($date);
        return date_format($d, 'F j, Y');
    }

    static function dateTime($dateTime)
    {
        $d = date_create($dateTime);
        return date_format($d, 'F j, Y h:i A');
    }
    
    static function time($time)
    {
        return substr($time, 0, -3);
    }

    static function convert_from_latin1_to_utf8_recursively($dat)
    {
        if (is_string($dat)) {
            return utf8_encode($dat);
        } elseif (is_array($dat)) {
            $ret = [];
            foreach ($dat as $i => $d) $ret[ $i ] = self::convert_from_latin1_to_utf8_recursively($d);

            return $ret;
        } elseif (is_object($dat)) {
            foreach ($dat as $i => $d) $dat->$i = self::convert_from_latin1_to_utf8_recursively($d);

            return $dat;
        } else {
            return $dat;
        }
    }

    static function utf8ize($d) {
        if (is_array($d)) {
            foreach ($d as $k => $v) {
                $d[$k] = self::utf8ize($v);
            }
        } else if (is_string ($d)) {
            return utf8_encode($d);
        }

        return $d;
    }
}
