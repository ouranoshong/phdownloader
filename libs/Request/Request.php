<?php
/**
 * Created by PhpStorm.
 * User: hong
 * Date: 16-10-1
 * Time: 下午4:07
 */

namespace PhDownloader\Request;

use PhDownloader\Enums\RequestFieldEnum;

class Request
{
    /**
     * @var  RequestHeaderCookies
     */
    public $cookies;

    /**
     * @var  RequestHeaderEntities
     */
    public $entities;

    /**
     * @var
     */
    public $headers;


    public $firstLine;


    public function addFirstLine($firstLine) 
    {
        $this->firstLine = $firstLine;
    }

    public function addHeader($name, $value) 
    {
        $this->headers[] = new RequestHeader($name, $value);
        return $this;
    }

    public function addHeaderAuthProxy($username, $password) 
    {
        $this->headers[] = new RequestHeaderAuthProxy($username, $password);
    }

    public function addHeaderAuth($username, $password) 
    {
        $this->headers[] = new RequestHeaderAuth($username, $password);
    }


    public function addCookie($name, $value) 
    {
        $this->cookies[] = new RequestCookie($name, $value);
        return $this;
    }

    public function addCookies($cookie) 
    {
        foreach((array)$cookie as $name=>$value) {
            $this->addCookie($name, $value);
        }

        return $this;
    }


    public function addEntity($name, $value) 
    {
        $this->entities[] = new RequestEntity($name, $value);
        return $this;
    }

    public function addEntities($entities) 
    {
        foreach((array)$entities as $name=>$value) {
            $this->addEntity($name, $value);
        }

        return $this;
    }


    public function __toString()
    {
        return $this->buildHeader().RequestFieldEnum::SEPARATOR.$this->buildEntityBody();
    }

    protected function buildHeader()
    {
        $sp = RequestFieldEnum::SEPARATOR;

        $headerLines = [$this->firstLine];

        foreach((array)$this->headers as $Header) {
            $headerLines[] = (string)$Header;
        }

        $cookie = count($this->cookies) > 0 ? (string)(new RequestHeaderCookies($this->cookies)) : '';

        return join(
            $sp,
            array_filter(
                array_merge($headerLines, [$cookie])
            )
        ).$sp;
    }

    public function buildEntityBody()
    {
        return (string)(new RequestHeaderEntities($this->entities)).RequestFieldEnum::SEPARATOR;
    }

}
