<?php
/**
 * Created by PhpStorm.
 * User: hong
 * Date: 16-10-1
 * Time: 下午4:32
 */

namespace PhDownloader\Request;

class RequestHeaderCookies extends RequestHeader
{
    public $name = 'Cookie';

    public $cookies = [];

    public function __construct($cookies = [])
    {
        $this->cookies = $cookies;

        $this->setCookiesValue($cookies);
    }

    public function setCookiesValue($cookies) 
    {
        /**@var $cookie \PhDownloader\Request\RequestCookie */
        foreach((array)$cookies as $cookie) {
            $this->value .= "; ".$cookie;
        }

        if ($this->value) { $this->value = substr($this->value, 2);
        }
    }

}
