<?php
/**
 * Created by PhpStorm.
 * User: hong
 * Date: 16-11-1
 * Time: 下午10:14
 */

namespace PhDownloader;


interface DownloaderInterface
{
    const TIME_DATA_TRANSFER = 'data_transfer_time';

    const TIME_SERVER_RESPONSE = 'server_response_time';

    const TIME_SERVER_CONNECT = 'server_connecting_time';
}
