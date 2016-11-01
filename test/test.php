<?php
/**
 * Created by PhpStorm.
 * User: hong
 * Date: 16-10-15
 * Time: 下午12:48
 */


require __DIR__ . '/../vendor/autoload.php';

$link = new \PhDescriptors\LinkDescriptor(
    'http://local.dev'
);

$Request = new \PhDownloader\Downloader();
$Request->setUrl($link);

$DocumentInfo = $Request->fetch();


var_dump($DocumentInfo);
