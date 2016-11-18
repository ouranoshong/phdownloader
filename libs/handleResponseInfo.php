<?php
/**
 * Created by PhpStorm.
 * User: hong
 * Date: 9/30/16
 * Time: 5:17 PM
 */

namespace PhDownloader;

use PhDownloader\Response\ResponseInfo;
use PhDownloader\Response\ResponseHeader;

trait handleResponseInfo
{
    protected function initResponseInfo() 
    {
        $this->ResponseInfo = new ResponseInfo();
    }

    protected function setResponseInfoContent($raw) 
    {
        $this->ResponseInfo->content = $raw;
    }

    protected function setResponseInfoDTR($dtr_values) 
    {
        $this->ResponseInfo->data_transfer_rate = $dtr_values["data_transfer_rate"];
        $this->ResponseInfo->unbuffered_bytes_read = $dtr_values["unbuffered_bytes_read"];
        $this->ResponseInfo->data_transfer_time = $dtr_values["data_transfer_time"];
    }

    protected function setResponseInfoStatistics() 
    {
        $this->ResponseInfo->received_completely = $this->document_received_completely;
        $this->ResponseInfo->bytes_received = $this->content_bytes_received;
        $this->ResponseInfo->header_bytes_received = $this->header_bytes_received;

        $dtr_values = $this->calculateDataTransferRateValues();
        if ($dtr_values != null) {
            $this->setResponseInfoDTR($dtr_values);
        }
    }
}
