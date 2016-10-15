<?php
/**
 * Created by PhpStorm.
 * User: hong
 * Date: 9/30/16
 * Time: 4:36 PM
 */

namespace PhDownloader\Enums;


interface Timer
{
    const DATA_TRANSFER = 'data_transfer_time';

    const SERVER_RESPONSE = 'server_response_time';

    const SERVER_CONNECT = 'server_connecting_time';
}
