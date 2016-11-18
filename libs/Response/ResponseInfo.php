<?php
/**
 * Created by PhpStorm.
 * User: hong
 * Date: 9/30/16
 * Time: 5:20 PM
 */

namespace PhDownloader\Response;


class ResponseInfo
{
    /**
     * Flag indicating whether content was received from the page or file.
     *
     * @var     bool TRUE if the crawler received at least some source/content of this page or file.
     * @section 2 Content-related information
     */
    public $received = false;

    /**
     * Flag indicating whether content was completely received from the page or file.
     *
     * The conten of the current document may not be received comepletely due to settings made
     * with {@link PHPCrawler::setContentSizeLimit()) and/or {@link PHPCrawler::setTrafficLimit()}.
     *
     * @var     bool TRUE if the crawler received the complete source/content of this page or file.
     * @section 2 Content-related information
     */
    public $received_completely = false;

    /**
     * Will be true if the content was received into local memory.
     *
     * You will have access to the content of the current page or file through $pageInfo->source.
     *
     * @section 2 Content-related information
     * @var     bool
     */
    public $received_to_memory = false;


    /**
     * The number of bytes the crawler received of the content of the document.
     *
     * @var     int Received bytes
     * @section 2 Content-related information
     */
    public $bytes_received = 0;

    /**
     * The temporary file to which the content was received.
     *
     * Will be NULL if the content wasn't received to the temporary file.
     *
     * @var     string
     * @section 2 Content-related information
     */
    public $content_tmp_file = null;

    /**
     * Indicates whether an error occured while requesting/receiving the document.
     *
     * @var     bool TRUE if an error occured.
     * @section 8 Error-handling
     */
    public $error_occured = false;

    /**
     * The code of the error that perhaps occured while requesting/receiving the document.
     * (See PHPCrawlerRequestErrors::ERROR_... - constants)
     *
     * @var     int One of the {@link PHPCrawlerRequestErrors}::ERROR_ ... constants.
     * @section 8 Error-handling
     */
    public $error_code = null;

    /**
     * A representig, human readable string for the error that perhaps occured while requesting/receiving the document.
     *
     * @var     string A human readable error-string.
     * @section 8 Error-handling
     */
    public $error_message = null;

    /**
     * Indicated whether the traffic-limit set by the user was reached after downloading this document.
     *
     * @var bool  TRUE if traffic-limit was reached.
     */
    public $traffic_limit_reached = false;

    /**
     * The approximated data-transferrate for this document.
     *
     * The data transfer rate is calulated by the data-transfer-time and the number of bytes that were received
     * alltogether. The server-connect-time and response-time are NOT included, so this is an indicator for the
     * server (or local) bandwidth.
     *
     * This is an calculated value and gets more accurate with larger received documents.
     * It may not be avaliable for very small documents.
     *
     * @var     float The rate in bytes per seconds or NULL if the rate couldn't be determinated
     * @section 10 Benchmarks
     */
    public $data_transfer_rate = null;

    /**
     * The time it took to connect to the server
     *
     * @var     float  The time in seconds and milliseconds or NULL if connection could not be established
     * @section 10 Benchmarks
     */
    public $server_connect_time = null;

    /**
     * The server response time
     *
     * The response-time is the time the server needs to respond to a HTTP-request-header.
     *
     * @var     float Time in seconds and milliseconds or NULL if the server didn't respond
     * @section 10 Benchmarks
     */
    public $server_response_time = null;

    /**
     * The approximated time it took to receive the data of the document.
     *
     * The server-connect-time and response-time are NOT included.
     * It may not be avaliable for very small documents.
     *
     * @var     float The time in seconds and milliseconds or NULL if not avaliable
     * @section 10 Benchmarks
     */
    public $data_transfer_time = null;

    /**
     * Number of unbuffered bytes received
     *
     * It may not be avaliable for very small documents.
     *
     * @var     int The time in seconds and milliseconds or NULL if not avaliable
     * @section 10 Benchmarks
     */
    public $unbuffered_bytes_read = null;
}

