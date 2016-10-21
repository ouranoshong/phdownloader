<?php
/**
 * Created by PhpStorm.
 * User: hong
 * Date: 9/30/16
 * Time: 5:17 PM
 *
 *
 *
 */

namespace PhDownloader;

use PhDescriptors\LinkDescriptor;
use PhDescriptors\LinkPartsDescriptor;
use PhDownloader\Response\ResponseInfo;
use PhDownloader\Response\ResponseHeader;

trait handleResponseInfo
{
    protected function initResponseInfo() {

        $this->ResponseInfo = new ResponseInfo();

        $this->setResponseInfoUrl($this->LinkDescriptor);

        $this->setResponseInfoUrlParts($this->LinkPartsDescriptor);
    }

    protected function setResponseInfoResponseHeader(ResponseHeader $responseHeader) {
        $this->ResponseInfo->http_status_code = $responseHeader->http_status_code;
        $this->ResponseInfo->content_type = $responseHeader->content_type;
        $this->ResponseInfo->response_cookies = $responseHeader->cookies;
        $this->setResponseInfoHeaderReceived($responseHeader->header_raw);
    }

    protected function setResponseInfoHeaderSend($raw) {
        $this->ResponseInfo->header_send = $raw;
    }

    protected function setResponseInfoHeaderReceived($raw) {
        $this->ResponseInfo->header_received = $raw;
    }

    protected function setResponseInfoContent($raw) {
        $this->ResponseInfo->content = $raw;
    }

    protected function setResponseInfoUrlParts(LinkPartsDescriptor $UrlParts) {
        $this->ResponseInfo->protocol = $UrlParts->protocol;
        $this->ResponseInfo->host = $UrlParts->host;
        $this->ResponseInfo->path = $UrlParts->path;
        $this->ResponseInfo->port = $UrlParts->port;
        $this->ResponseInfo->file = $UrlParts->file;
        $this->ResponseInfo->query = $UrlParts->query;
    }

    protected function setResponseInfoUrl(LinkDescriptor $Url) {
        $this->ResponseInfo->url = $Url->url_rebuild;
        $this->ResponseInfo->url_link_depth = $Url->url_link_depth;
        $this->ResponseInfo->referer_url = $Url->refering_url;
        $this->ResponseInfo->refering_link_code = $Url->link_code;
        $this->ResponseInfo->refering_link_raw = $Url->link_raw;
        $this->ResponseInfo->refering_link_text = $Url->link_text;
    }

    protected function setResponseInfoDTR($dtr_values) {
        $this->ResponseInfo->data_transfer_rate = $dtr_values["data_transfer_rate"];
        $this->ResponseInfo->unbuffered_bytes_read = $dtr_values["unbuffered_bytes_read"];
        $this->ResponseInfo->data_transfer_time = $dtr_values["data_transfer_time"];
    }

    protected function setResponseInfoStatistics() {
        $this->ResponseInfo->received_completely = $this->document_received_completely;
        $this->ResponseInfo->bytes_received = $this->content_bytes_received;
        $this->ResponseInfo->header_bytes_received = $this->header_bytes_received;

        $dtr_values = $this->calculateDataTransferRateValues();
        if ($dtr_values != null)
        {
            $this->setResponseInfoDTR($dtr_values);
        }
    }
}
