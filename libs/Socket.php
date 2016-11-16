<?php
/**
 * Created by PhpStorm.
 * User: hong
 * Date: 16-10-15
 * Time: 下午12:41
 */

namespace PhDownloader;

use PhUtils\DNSUtil;
use Psr\Http\Message\RequestInterface;

/**
 * Class Socket
 *
 * @package PhDownloader
 */
class Socket
{
    /**
     *
     */
    const SOCKET_PROTOCOL_PREFIX_SSL = 'ssl://';

    /**
     *
     */
    const ERROR_PROXY_UNREACHABLE = 101;

    /**
     *
     */
    const ERROR_SOCKET_TIMEOUT = 102;

    /**
     *
     */
    const ERROR_SSL_NOT_SUPPORTED = 103;

    /**
     *
     */
    const ERROR_HOST_UNREACHABLE = 104;

    /**
     * @var int
     */
    public $timeout = 6;
    /**
     * @var
     */
    public $error_code;
    /**
     * @var
     */
    public $error_message;

    /**
     * @var array
     */
    protected $standardPorts = [
        'http' => 80,
        'https' => 443
    ];


    /**
     * @var \Psr\Http\Message\RequestInterface
     */
    protected $request;

    /**
     * @var resource
     */
    protected $_socket;


    /**
     * Socket constructor.
     *
     * @param \Psr\Http\Message\RequestInterface $request
     */
    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * @return bool
     */
    private function isSSLConnection() {
        return $this->request->getUri()->getScheme() === 'https';
    }

    /**
     * @return bool
     */
    protected function canOpen()
    {
        if (!($this->request->getUri()->getHost())) {
            $this->error_code = self::ERROR_HOST_UNREACHABLE;
            $this->error_message = "Require connection information!";
            return false;

        }

        if ($this->isSSLConnection() && !extension_loaded("openssl")) {
            $UrlParts = $this->LinkParsDescriptor;
            $this->error_code = self::ERROR_SSL_NOT_SUPPORTED;
            $this->error_message = "Error connecting to ".$UrlParts->protocol.$UrlParts->host.": SSL/HTTPS-requests not supported, extension openssl not installed.";
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function open()
    {

        if (!$this->canOpen()) { return false; }

        $this->_socket = @stream_socket_client(
            $this->getClientRemoteURI(),
            $this->error_code,
            $this->error_message,
            $this->timeout,
            STREAM_CLIENT_CONNECT,
            $this->getClientContext()
        );

        return $this->checkOpened();
    }

    /**
     * @return bool
     */
    protected function checkOpened()
    {
        if ($this->_socket == false) {
            // If proxy not reachable
            $host = $this->request->getUri()->getScheme() . '://'. $this->request->getUri()->getHost();
            if ($this->request->getUri()->getPort()) {
                $host .= ':'.$this->request->getUri()->getPort();
            }
            $this->error_code = self::ERROR_HOST_UNREACHABLE;
            $this->error_message = "Error connecting to $host: Host unreachable (".$this->error_message.").";
            return false;
        }
        return true;
    }

    /**
     * @return string
     */
    protected function getClientRemoteURI()
    {
        $protocol_prefix = '';
        $host = DNSUtil::getIpByHostName($this->request->getUri()->getHost());
        $port = $this->request->getUri()->getPort() ? : $this->standardPorts[$this->request->getUri()->getScheme()];

        if ($this->isSSLConnection()) {
            $host = $this->request->getUri()->getHost();
            $protocol_prefix = self::SOCKET_PROTOCOL_PREFIX_SSL;
        }

        return $protocol_prefix . $host . ':'.$port;
    }

    /**
     * @return resource
     */
    protected function getClientContext()
    {
        if ($this->isSSLConnection()) {
            return @stream_context_create(['ssl' => array('peer_name' => $this->request->getUri()->getHost())]);
        }
        return @stream_context_create();
    }

    /**
     *
     */
    public function close()
    {
        @fclose($this->_socket);
    }

    /**
     * @param string $message
     *
     * @return int
     */
    public function send($message = '')
    {
        return @fwrite($this->_socket, $message, strlen($message));
    }

    /**
     * @param int $buffer
     *
     * @return string
     */
    public function read($buffer = 1024)
    {
        return @fread($this->_socket, $buffer);
    }

    /**
     * @param int $buffer
     *
     * @return string
     */
    public function gets($buffer = 128)
    {
        return @fgets($this->_socket, $buffer);
    }

    /**
     * @param null $timeout
     *
     * @return bool
     */
    public function setTimeOut($timeout = null)
    {

        if ($timeout) {
            $this->timeout = $timeout;
        }

        return @socket_set_timeout($this->_socket, $this->timeout);
    }

    /**
     * @return array
     */
    public function getStatus()
    {
        return @socket_get_status($this->_socket);
    }

    /**
     * @return bool
     */
    public function checkTimeoutStatus()
    {
        $status = $this->getStatus();
        if ($status["timed_out"] == true) {
            $this->error_code = self::ERROR_SOCKET_TIMEOUT;
            $this->error_message = "Socket-stream timed out (timeout set to ".$this->timeout." sec).";
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isEOF()
    {
        return ($this->getStatus()["eof"] == true || feof($this->_socket) == true);
    }

    /**
     * @return mixed
     */
    public function getUnreadBytes()
    {
        return $this->getStatus()['unread_bytes'];
    }

}
