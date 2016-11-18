<?php
/**
 * Created by PhpStorm.
 * User: hong
 * Date: 16-10-15
 * Time: 下午12:36.
 */

namespace PhDownloader;

use PhDescriptors\LinkDescriptor;
use PhDescriptors\LinkPartsDescriptor;
use PhDescriptors\ProxyDescriptor;
use PhDownloader\Enums\Protocols;
use PhDownloader\Response\ResponseInfo;
use PhDownloader\Response\ResponseHeader;
use PhMessage\Response;
use PhMessage\Stream;
use PhMessage\StreamWrapper;
use Psr\Http\Message\RequestInterface;

class Downloader implements DownloaderInterface
{
    use handleResponseInfo,
        handleRequestHeader,
        handleResponseHeader,
        handleResponseBody;

    const METHOD_GET = 'GET';

    const METHOD_POST = 'POST';

    const HTTP_VERSION_1_0 = '1.0';

    const HTTP_VERSION_1_1 = '1.1';

    /**
     * @var string
     */
    public $userAgent = 'PhDownloader';

    /**
     * @var bool
     */
    protected $request_gzip_content = true;
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
    protected $LinkDescriptor;

    /**
     * @var LinkPartsDescriptor
     */
    protected $LinkPartsDescriptor;

    /**
     * @var ProxyDescriptor
     */
    protected $ProxyDescriptor;

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
    protected $ResponseHeader;

    /**
     * @var ResponseInfo
     */
    protected $ResponseInfo;

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
     * @var RequestInterface
     */
    protected $request;

    public function sendRequest(RequestInterface $request, $options = [])
    {
        $this->request = $request;

        $this->init();

        if (!$this->openSocket()) {
            return $this->ResponseInfo;
        }

        $this->sendRequestContent();

        return $this->readResponseContent();
    }

    /**
     * @throws \Exception
     */
    protected function init()
    {
        if (!$this->request) {
            throw new \Exception('Require connection information!');
        }
        $this->initResponseInfo();
    }

    /**
     * @return bool
     */
    protected function openSocket()
    {
        $this->Socket = $Socket = new Socket($this->request);

        \PhBench\reset_benchmarks(DownloaderInterface::TIME_SERVER_CONNECT);
        \PhBench\start_benchmark(DownloaderInterface::TIME_SERVER_CONNECT);

        if (!$Socket->open()) {
            $this->setServerConnectTime();
            $this->SetErrorMessage($Socket->error_message);
            $this->SetErrorCode($Socket->error_code);

            return false;
        }

        $this->setServerConnectTime(\PhBench\stop_benchmark(DownloaderInterface::TIME_SERVER_CONNECT));

        return true;
    }

    protected function sendRequestContent()
    {

        $requestHeaderRaw = $this->buildRequestHeaderRaw();
        $this->Socket->send($requestHeaderRaw);

    }

    /**
     * @return ResponseInfo
     */
    protected function readResponseContent()
    {
        $responseHeaderRaw = $this->readResponseHeader();

        $response = \PhMessage\parse_response($responseHeaderRaw);

        $receive = $this->decideReceiveContent(new ResponseHeader($responseHeaderRaw, (string)$this->request->getUri()));

        if ($receive === false) {

            $this->Socket->close();

            return $response;
        }

        $body = $this->readResponseBody();

        if ($body) {
            $response = $response->withBody(\PhMessage\stream_for($body));
        }

//        $this->setResponseInfoStatistics();

        return [$this->ResponseInfo, $response];
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

        if ($data_transfer_time > 0 && ($this->content_bytes_received > 4 * $this->socket_pre_fill_size)) {
            $dataValues['unbuffered_bytes_read'] = $this->content_bytes_received + $this->header_bytes_received - $this->socket_pre_fill_size;
            $dataValues['data_transfer_rate'] = $dataValues['unbuffered_bytes_read'] / $data_transfer_time;
            $dataValues['data_transfer_time'] = $data_transfer_time;
        } else {
            $dataValues = null;
        }

        return $dataValues;
    }

    /**
     * @param null $time
     */
    protected function setServerConnectTime($time = null)
    {
        $this->ResponseInfo->server_connect_time = $time;
    }

    /**
     * @param null $time
     */
    protected function setServerResponseTime($time = null)
    {
        $this->ResponseInfo->server_response_time = $time;
    }

    /**
     * @param null $time
     */
    protected function setDataTransferTime($time = null)
    {
        $this->ResponseInfo->data_transfer_time = $time;
    }

    /**
     * @return float
     */
    public function getDataTransferTime()
    {
        return $this->ResponseInfo->data_transfer_time;
    }

    /**
     * @param $message
     */
    protected function setErrorMessage($message)
    {
        $this->ResponseInfo->error_occured = true;
        $this->ResponseInfo->error_message = $message;
    }

    /**
     * @param $code
     */
    protected function setErrorCode($code)
    {
        $this->ResponseInfo->error_occured = true;
        $this->ResponseInfo->error_code = $code;
    }
}
