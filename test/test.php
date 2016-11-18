<?php
/**
 * Created by PhpStorm.
 * User: hong
 * Date: 16-10-15
 * Time: 下午12:48
 */


require __DIR__ . '/../vendor/autoload.php';

$link = new \PhDescriptors\LinkDescriptor(
    'https://www.baidu.com'
);

$request = new \PhMessage\Request('GET', 'https://www.baidu.com');

$Request = new \PhDownloader\Downloader();
$DocumentInfo = $Request->sendRequest($request);


var_dump($DocumentInfo);
