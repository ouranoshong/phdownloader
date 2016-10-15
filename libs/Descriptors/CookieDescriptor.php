<?php
/**
 * Created by PhpStorm.
 * User: hong
 * Date: 9/29/16
 * Time: 3:29 PM
 */

namespace PhDownloader\Descriptors;

class CookieDescriptor
{
    /**
     * Cookie-name
     *
     * @var string
     */
    public $name;

    /**
     * Cookie-value
     *
     * @var string
     */
    public $value;

    /**
     * Expire-string, e.g. "Sat, 08-Aug-2020 23:59:08 GMT"
     *
     * @var string
     */
    public $expires = null;

    /**
     * Expire-date as unix-timestamp
     *
     * @var int
     */
    public $expire_timestamp = null;

    /**
     * Cookie-path
     *
     * @var string
     */
    public $path = null;

    /**
     * Cookie-domain
     *
     * @var string
     */
    public $domain = null;

    /**
     * The domain the cookie was send from
     *
     * @var string
     */
    public $source_domain = null;

    /**
     * The URL the cookie was send from
     *
     * @var string
     */
    public $source_url = null;

    /**
     * The time the cookie was send
     *
     * @var float time in secs and microseconds
     */
    public $cookie_send_time = null;

    /**
     * Initiates a new CookieDescriptor-object.
     *
     * @param string $source_url URL the cookie was send from.
     * @param string $name       Cookie-name
     * @param string $value      Cookie-value
     * @param string $expires    Expire-string, e.g. "Sat, 08-Aug-2020 23:59:08 GMT"
     * @param string $path       Cookie-path
     * @param string $domain     Cookie-domain
     *
     */
    public function __construct($source_url, $name, $value, $expires = null, $path = null, $domain = null)
    {
        // For cookie-specs, see e.g. http://curl.haxx.se/rfc/cookie_spec.html
        $this->init($source_url, $name, $value, $expires, $path, $domain);
    }

    public function init($source_url, $name, $value, $expires = null, $path = null, $domain = null) {
        $this->name = $name;
        $this->value = $value;
        $this->expires = $expires;
        $this->path = $path;
        $this->domain = $domain;

        $UrlParts = new LinkPartsDescriptor($source_url);

        // Source-domain
        $this->source_domain = $UrlParts->domain;

        // Source-URL
        $this->source_url = $source_url;

        // Send-time
        $this->cookie_send_time = microtime(true);

        // Expire-date to timetsamp
        if ($this->expires != null)
            $this->expire_timestamp = @strtotime($this->expires);

        // If domain doesn't start with "." -> add it (see RFC)
        if ($this->domain != null && substr($this->domain, 0, 1) != ".") $this->domain = ".".$this->domain;

        // Comeplete missing values

        // If domain no set -> domain is the host of the source-url WITHOUT leading "."! (see RFC)
        if ($this->domain == null) $this->domain = $UrlParts->host;

        // If path not set
        if ($this->path == null) $this->path = $UrlParts->path;
    }

    /**
     * Returns a CookieDescriptor-object initiated by the given cookie-header-line.
     *
     * @param string $header_line The line from an header defining the cookie, e.g. "VISITOR=4c63394c2d82e31552001a58; expires="Sat, 08-Aug-2020 23:59:08 GMT"; Path=/"
     * @param string $source_url  URL the cookie was send from.
     * @return CookieDescriptor The appropriate CookieDescriptor-object.
     *
     */
    public static function getFromHeaderLine($header_line, $source_url)
    {
        $parts = explode(";", trim($header_line));

        $name = "";
        $value = "";
        $expires = null;
        $path = null;
        $domain = "";

        // Name and value
        preg_match("#([^=]*)=(.*)#", $parts[0], $match);
        $name = trim($match[1]);
        $value = trim($match[2]);

        // Path and Expires
        for ($x=1; $x<count($parts); $x++)
        {
            $parts[$x] = trim($parts[$x]);

            if (preg_match("#^expires\s*=(.*)# i", $parts[$x], $match)) $expires = trim($match[1]);
            if (preg_match("#^path\s*=(.*)# i", $parts[$x], $match)) $path = trim($match[1]);
            if (preg_match("#^domain\s*=(.*)# i", $parts[$x], $match)) $domain = trim($match[1]);
        }

        $expires = str_replace("\"", "", $expires);
        $path = str_replace("\"", "", $path);
        $domain = str_replace("\"", "", $domain);

        return new CookieDescriptor($source_url, $name, $value, $expires, $path, $domain);
    }
}
