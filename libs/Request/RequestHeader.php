<?php
/**
 * Created by PhpStorm.
 * User: hong
 * Date: 16-10-1
 * Time: 下午4:00
 */

namespace PhDownloader\Request;

use PhDownloader\Contracts\handleRequestField;
use PhDownloader\Contracts\RequestField;

class RequestHeader implements RequestField
{
    use handleRequestField;

}
