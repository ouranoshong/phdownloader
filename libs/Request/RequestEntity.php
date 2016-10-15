<?php
/**
 * Created by PhpStorm.
 * User: hong
 * Date: 16-10-1
 * Time: 下午4:07
 */

namespace PhDownloader\Request;

use PhDownloader\Contracts\handleRequestField;
use PhDownloader\Contracts\RequestField;
use PhDownloader\Enums\RequestFieldEnum;

class RequestEntity implements RequestField, RequestFieldEnum
{
    use handleRequestField;

    public function __toString()
    {

        $post_content = "Content-Disposition: form-data; name=\"" . $this->name . "\"";
        $post_content .= self::SEPARATOR . self::SEPARATOR;
        $post_content .= $this->value;
        $post_content .= self::SEPARATOR;

        return $post_content;
    }

}
