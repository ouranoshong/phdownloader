<?php
/**
 * Created by PhpStorm.
 * User: hong
 * Date: 16-9-28
 * Time: 下午8:45.
 */

namespace PhDownloader;

//use PhDescriptors\ProxyDescriptor;
//use PhDescriptors\LinkPartsDescriptor;
//use PhDownloader\Enums\RequestFieldEnum;
//use PhDownloader\Request\Request;
use PhUtils\EncodingUtil;
use PhUtils\LinkUtil;
use Psr\Http\Message\RequestInterface;

/**
 * Class RequestHeader.
 */
trait handleRequestHeader
{
    protected function buildRequestHeaderRaw()
    {

        /** @var RequestInterface $request */
        $request = $this->request;

        $request = \PhMessage\modify_request($request,
            ['set_headers' => [
                'User-Agent' => $this->userAgent,
                'Accept' => '*/*',
            ]]);

        if ($this->request_gzip_content === true)
        {
            $request = \PhMessage\modify_request($request, ['Accept-Encoding' => 'gzip, deflate']);
        }

        if (! $request->hasHeader('Connection'))
        {
            $request = $request->withHeader('Connection', 'closed');
        }

        return \PhMessage\str($request);
    }
}
