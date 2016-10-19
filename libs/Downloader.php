<?php
/**
 * Created by PhpStorm.
 * User: hong
 * Date: 16-10-15
 * Time: 下午12:36
 */

namespace PhDownloader;


use PhDownloader\Descriptors\LinkDescriptor;
use PhDownloader\Descriptors\LinkPartsDescriptor;
use PhDownloader\Descriptors\ProxyDescriptor;
use PhDownloader\Enums\Protocols;
use PhDownloader\Enums\Timer;
use PhDownloader\Response\ResponseInfo;
use PhDownloader\Response\ResponseHeader;
use PhUtils\Benchmark;

class Downloader
{
    use handleResponseInfo,
        handleRequestHeader,
        handleResponseHeader,
        handleResponseBody;

    /**
     *
     */
    const METHOD_GET = 'GET';
    /**
     *
     */
    const METHOD_POST = 'POST';

    /**
     *
     */
    const HTTP_VERSION_1_0 = '1.0';
    /**
     *
     */
    const HTTP_VERSION_1_1 = '1.1';


    /**
     * @var string
     */
    public $userAgent = 'PhDownloader';

    /**
     * @var bool
     */
    public $request_gzip_content = true;
    /**
     * @var string
     */
    protected $method;
    /**
     * @var string
     */
    protected $url;

    /**
     * @var string
     */
    protected $http_protocol_version;

    /**
     * @var LinkDescriptor
     */
    public $LinkDescriptor;

    /**
     * @var LinkPartsDescriptor
     */
    public $LinkPartsDescriptor;

    /**
     * @var ProxyDescriptor
     */
    public $ProxyDescriptor;

    /**
     * @var array
     */
    protected $cookie_data = [];

    /**
     * @var array
     */
    protected $post_data = [];

    /**
     * @var int
     */
    protected $header_bytes_received = 0;

    /**
     * @var null
     */
    protected $server_response_time = null;

    /**
     * @var
     */
    protected $error_code;

    /**
     * @var
     */
    protected $error_message;


    /**
     * @var ResponseHeader
     */
    public $ResponseHeader;

    /**
     * @var ResponseInfo
     */
    public $ResponseInfo;

    /**
     * @var Socket
     */
    protected $Socket;

    /**
     * @var
     */
    protected $document_completed;

    /**
     * @var
     */
    protected $document_received_completely;

    /**
     * @var null | \Closure
     */
    public $response_header_check_callback_function = null;

    /**
     * @var int
     */
    protected $content_buffer_size = 200000;
    /**
     * @var int
     */
    protected $chunk_buffer_size = 20240;
    /**
     * @var int
     */
    protected $socket_read_buffer_size = 1024;
    /**
     * @var int
     */
    protected $source_overlap_size = 1500;

    /**
     * @var int
     */
    protected $content_size_limit = 0;

    /**
     * @var float | null
     */
    protected $content_bytes_received = null;

    /**
     * @var int
     */
    protected $global_traffic_count = 0;

    /**
     * @var null
     */
    protected $server_connect_time = null;

    /**
     * @var null
     */
    protected $socket_pre_fill_size = null;

    /**
     * @var null
     */
    protected $receive_content_types = null;


    /**
     * Sets the URL for the request.
     *
     * @param LinkDescriptor $UrlDescriptor An URLDescriptor-object containing the URL to request
     */
    public function setUrl(LinkDescriptor $UrlDescriptor)
    {
        $this->LinkDescriptor = $UrlDescriptor;

        if (!$this->LinkPartsDescriptor) {

            $this->LinkPartsDescriptor = new LinkPartsDescriptor();

        }

        $this->LinkPartsDescriptor->init($this->LinkDescriptor->url_rebuild);
    }

    /**
     * Adds a cookie to send with the request.
     *
     * @param string $name Cookie-name
     * @param string $value Cookie-value
     */
    public function addCookie($name, $value)
    {
        $this->cookie_data[$name] = $value;
    }

    /**
     * Adds a cookie to send with the request.
     *
     * @param CookieDescriptor $Cookie
     */
    public function addCookieDescriptor(CookieDescriptor $Cookie)
    {
        $this->addCookie($Cookie->name, $Cookie->value);
    }

    /**
     * Adds a bunch of cookies to send with the request
     *
     * @param array $cookies Numeric array containins cookies as CookieDescriptor-objects
     */
    public function addCookieDescriptors($cookies)
    {
        $cnt = count($cookies);
        for ($x=0; $x<$cnt; $x++)
        {
            $this->addCookieDescriptor($cookies[$x]);
        }
    }

    /**
     * Removes all cookies to send with the request.
     */
    public function clearCookies()
    {
        $this->cookie_data = array();
    }


    /**
     * @param $key
     * @param $value
     */
    public function addPostData($key, $value)
    {
        $this->post_data[$key] = $value;
    }

    /**
     * Removes all post-data to send with the request.
     */
    public function clearPostData()
    {
        $this->post_data = array();
    }


    public function setProxy(ProxyDescriptor $Proxy)
    {
        $this->ProxyDescriptor = $Proxy;
    }


    /**
     * @param $username
     * @param $password
     */
    public function setBasicAuthentication($username, $password)
    {
        if (!($this->LinkPartsDescriptor instanceof LinkPartsDescriptor)) {
            $this->LinkPartsDescriptor = new LinkPartsDescriptor();
        }

        $this->LinkPartsDescriptor->auth_username = $username;
        $this->LinkPartsDescriptor->auth_password = $password;
    }


    /**
     *
     */
    public function fetch() {

        $this->init();

        if (!$this->openSocket()) return $this->ResponseInfo;

        $this->sendRequestContent();

        return $this->readResponseContent();
    }

    /**
     * @throws \Exception
     */
    protected function init() {
        if (!$this->LinkDescriptor) {
            throw new \Exception('Require connection information!');
        }

        if (!$this->LinkPartsDescriptor) {
            $this->LinkPartsDescriptor = new LinkPartsDescriptor(
                $this->LinkDescriptor->url_rebuild
            );
        } else if (!$this->LinkPartsDescriptor->host) {
            $this->LinkPartsDescriptor->init($this->LinkDescriptor->url_rebuild);
        }

        if (!$this->http_protocol_version) {
            $this->http_protocol_version = Protocols::HTTP_1_1;
        }

        $this->initResponseInfo();
    }

    /**
     * @return bool
     */
    protected function openSocket() {

        $this->Socket = $Socket = new Socket();
        $Socket->LinkParsDescriptor = $this->LinkPartsDescriptor;

        Benchmark::reset(Timer::SERVER_CONNECT);
        Benchmark::start(Timer::SERVER_CONNECT);

        if (!$Socket->open()) {

            $this->setServerConnectTime();
            $this->SetErrorMessage($Socket->error_message);
            $this->SetErrorCode($Socket->error_code);

            return false;
        }


        $this->setServerConnectTime(Benchmark::stop(Timer::SERVER_CONNECT));

        return true;
    }

    /**
     *
     */
    protected function sendRequestContent() {

        $requestHeaderRaw = $this->buildRequestHeaderRaw();

        $this->setResponseInfoHeaderSend($requestHeaderRaw);

        $this->Socket->send($requestHeaderRaw);

    }

    /**
     * @return ResponseInfo
     */
    protected function readResponseContent() {

        $responseHeaderRaw = $this->readResponseHeader();

        $this->setResponseInfoHeaderReceived($responseHeaderRaw);

        $this->ResponseHeader = new ResponseHeader($responseHeaderRaw, $this->LinkDescriptor->url_rebuild);

        $this->setResponseInfoResponseHeader($this->ResponseHeader);

        $receive = $this->decideReceiveContent($this->ResponseHeader);

        if ($receive == false)
        {
            $this->Socket->close();
            $this->ResponseInfo->received = false;

            return $this->ResponseInfo;
        }
        else
        {
            $this->ResponseInfo->received = true;
        }

        $this->setResponseInfoContent($this->readResponseBody());

        $this->setResponseInfoStatistics();

        return $this->ResponseInfo;
    }

    /**
     * @return array|null
     */
    protected function calculateDataTransferRateValues()
    {

        $dataValues = array();

        $data_transfer_time = $this->getDataTransferTime();
        // Works like this:
        // After the server resonded, the socket-buffer is already filled with bytes,
        // that means they were received within the server-response-time.

        // To calulate the real data transfer rate, these bytes have to be substractred from the received
        // bytes beofre calulating the rate.

        if ($data_transfer_time > 0 && $this->content_bytes_received > 4 * $this->socket_pre_fill_size)
        {
            $dataValues["unbuffered_bytes_read"] = $this->content_bytes_received + $this->header_bytes_received - $this->socket_pre_fill_size;
            $dataValues["data_transfer_rate"] = $dataValues["unbuffered_bytes_read"] / $data_transfer_time;
            $dataValues["data_transfer_time"] = $data_transfer_time;
        }
        else
        {
            $dataValues = null;
        }

        return $dataValues;
    }


    /**
     * @param null $time
     */
    protected function setServerConnectTime($time = null) {
        $this->ResponseInfo->server_connect_time = $time;
    }

    /**
     * @param null $time
     */
    protected function setServerResponseTime($time = null) {
        $this->ResponseInfo->server_response_time = $time;
    }

    /**
     * @param null $time
     */
    protected function setDataTransferTime($time = null) {
        $this->ResponseInfo->data_transfer_time = $time;
    }

    /**
     * @return float
     */
    public function getDataTransferTime() {
        return $this->ResponseInfo->data_transfer_time;
    }

    /**
     * @param $message
     */
    protected function setErrorMessage($message) {
        $this->ResponseInfo->error_occured = true;
        $this->ResponseInfo->error_message = $message;
    }

    /**
     * @param $code
     */
    protected function setErrorCode($code) {
        $this->ResponseInfo->error_occured = true;
        $this->ResponseInfo->error_code = $code;
    }

}
