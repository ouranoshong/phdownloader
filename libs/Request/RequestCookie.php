<?php
/**
 * Created by PhpStorm.
 * User: hong
 * Date: 16-10-1
 * Time: 下午4:08
 */

namespace PhDownloader\Request;

use PhDownloader\Contracts\RequestField;
use PhDownloader\Contracts\handleRequestField;

class RequestCookie implements RequestField
{
    use handleRequestField;

    public function __toString()
    {
        return $this->generateCookieField();
    }

    protected function generateCookieField() 
    {
        return $this->name . '='. $this->value;
    }
}
