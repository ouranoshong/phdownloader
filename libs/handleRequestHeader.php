<?php
/**
 * Created by PhpStorm.
 * User: hong
 * Date: 16-9-28
 * Time: 下午8:45
 */

namespace PhDownloader;


use PhDescriptors\ProxyDescriptor;
use PhDescriptors\LinkPartsDescriptor;
use PhDownloader\Enums\RequestFieldEnum;
use PhDownloader\Request\Request;
use PhUtils\EncodingUtil;
use PhUtils\LinkUtil;

/**
 * Class RequestHeader
 *
 * @package PhDownloader\Http
 */
trait handleRequestHeader
{

    protected function initRequestHeader() {

        if (!$this->method) {
            if (count($this->post_data) > 0) $this->method = Downloader::METHOD_POST;
            else $this->method = Downloader::METHOD_GET;
        }

    }


    /**
     * @return string
     */
    protected function buildRequestFirstLine()
    {
        $_Proxy = $this->ProxyDescriptor;

        if ($_Proxy instanceof Proxy && $_Proxy->host != null) {
            // A Proxy needs the full qualified URL in the GET or POST headerline.
            $line = $this->method . " " . $this->LinkDescriptor->url_rebuild . " HTTP/1.0";
        } else {
            $query = $this->buildRequestQuery();
            $line = $this->method . " " . $query . " HTTP/" . $this->http_protocol_version;
        }
        return $line;
    }

    /**
     *
     * @return mixed|string
     */
    protected function buildRequestQuery()
    {
        $UrlParts = $this->LinkPartsDescriptor;

        $query = $UrlParts->path . $UrlParts->file . $UrlParts->query;
        // If string already is a valid URL -> do nothing
        if (LinkUtil::isValidUrlString($query)) {
            return $query;
        }

        // Decode query-string (for URLs that are partly urlencoded and partly not)
        $query = rawurldecode($query);

        // if query is already utf-8 encoded -> simply urlencode it,
        // otherwise encode it to utf8 first.
        if (EncodingUtil::isUTF8String($query) == true) {
            $query = rawurlencode($query);
        } else {
            $query = rawurlencode(utf8_encode($query));
        }

        // Replace url-specific signs back
        $query = str_replace("%2F", "/", $query);
        $query = str_replace("%3F", "?", $query);
        $query = str_replace("%3D", "=", $query);
        $query = str_replace("%26", "&", $query);

        return $query;
    }

    protected   function buildRequestHeaderRaw() {
        $this->initRequestHeader();

        $Request = new Request();

        $Request->firstLine = $this->buildRequestFirstLine();

        $Request->addHeader('Host', $this->LinkPartsDescriptor->host)
            ->addHeader('User-Agent', $this->userAgent)
            ->addHeader('Accept', '*/*');

        if ($this->request_gzip_content === true) {
            $Request->addHeader('Accept-Encoding', 'gzip, deflate');
        }

        if($this->LinkDescriptor->refering_url){
            $Request->addHeader('Referer', $this->LinkDescriptor->refering_url);
        }

        if (count($this->cookie_data)) {
            $Request->addCookies($this->cookie_data);
        }

        $UrlParts = $this->LinkPartsDescriptor;

        if ($UrlParts instanceof LinkPartsDescriptor &&
            ($UrlParts->auth_username != "") &&
            ($UrlParts->auth_password != "")) {

            $Request->addHeaderAuth($UrlParts->auth_username, $UrlParts->auth_password);
        }

        $Proxy = $this->ProxyDescriptor;

        if ($Proxy instanceof ProxyDescriptor &&
            $Proxy->username != null) {
            $Request->addHeaderAuthProxy($Proxy->username, $Proxy->password);
        }

        $Request->addHeader('Connection', 'closed');


        if ($this->method == self::METHOD_POST) {
            // Post-Content
            $Request->addEntities($this->post_data);
            $post_content =  $Request->buildEntityBody();

            $Request->addHeader('Content-Type', 'multipart/form-data; boundary='.RequestFieldEnum::ENTITY_BOUNDARY);

            $Request->addHeader('Content-length', strlen($post_content));
        }

        return (string)$Request;
    }
}
