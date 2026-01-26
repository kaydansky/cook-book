<?php

namespace Cookbook\Helpers;

/**
 * Description of Sanitizer
 *
 * @author AlexK
 */
class Sanitizer
{
    public function __construct(){}
    
    static function sanitize($var = null, $length = 1000) 
    {
        if (! empty($var)) {
            return substr(trim(urldecode($var)), 0, $length);
        }
    }
}
