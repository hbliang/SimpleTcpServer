<?php


namespace Hbliang\SimpleTcpServer;


use Hbliang\SimpleTcpServer\Exceptions\BadRequestException;

class Http
{
    // supported methods
    const METHODS = ['GET', 'POST', 'OPTIONS', 'DELETE', 'HEAD', 'PUT'];

    /**
     * @see https://mdref.m6w6.name/http/Message
     * @var
     */
    protected $httpMessageParser;

    /**
     * @var string
     */
    protected $rawInput;

    protected $method;

    protected $schema;

    protected $host;

    protected $port;

    protected $requestUri;

    protected $headers = [];

    protected $GET, $POST, $COOKIE, $FILES, $SERVER = [];

    public function __construct($rawInput)
    {
        $this->rawInput = $rawInput;

        if (!extension_loaded('http')) {
            throw new \Exception('Extension http doesn\'t load');
        }
        $this->httpMessageParser = new \http\Message($rawInput);
    }

    public function parse()
    {
        $this->headers = $this->httpMessageParser->getHeaders();

        list($this->method, $this->requestUri, $serverProtocol) = explode(' ', trim($this->httpMessageParser->getInfo()));
        if (!in_array($this->method, self::METHODS)) {
            throw new BadRequestException("Method {$this->method} is unsupported");
        }

        /**
         * there are two host
         * 1. with port: 127.0.0.1:8000
         * 2. without port: 127.0.0.1 -> so port is 80
         */
        if (!$this->host = $this->headers['Host'] ?? null) {
            throw new BadRequestException("Require Host");
        }
        if (($colonOffset = strpos($this->host, ':')) !== false) {
            $this->host = strstr($this->host, ':', true);
            $this->port = intval(substr($this->host, $colonOffset + 1));
        } else {
            $this->port = 80;
        }

        $this->schema = strstr($serverProtocol, '/', true);

        $queryString = parse_url($this->requestUri, PHP_URL_QUERY);
        parse_str($queryString, $this->GET);

        parse_str($this->headers['Cookie'] ?? '', $this->COOKIE);

        // TODO post and file
        $this->POST = [];
        $this->FILES = [];

        $this->SERVER = [
            'QUERY_STRING' => $queryString,
            'REQUEST_METHOD' => $this->method,
            'REQUEST_URI' => $this->requestUri,
            'SERVER_PROTOCOL' => $serverProtocol,
            'SERVER_SOFTWARE' => '',
            'SERVER_NAME' => '',
            'HTTP_HOST' => parse_url($this->headers['Host'], PHP_URL_HOST),
            'HTTP_USER_AGENT' => $this->headers['User-Agent'] ?? '',
            'HTTP_ACCEPT' => $this->headers['Accept'] ?? '',
            'HTTP_ACCEPT_LANGUAGE' => $this->headers['Accept-Language'] ?? '',
            'HTTP_ACCEPT_ENCODING' => $this->headers['Accept-Encoding'] ?? '',
            'HTTP_COOKIE' => $this->headers['Cookie'] ?? '',
            'HTTP_CONNECTION' => $this->headers['Connection'] ?? '',
            'REMOTE_ADDR' => '',
            'REMOTE_PORT' => '0',
            'CONTENT_LENGTH' => $this->headers['Content-Length'] ?? '',
            'REQUEST_TIME' => time(),
        ];
        $contentType = $this->headers['Content-Type'] ?? 'text/html';
        if (!preg_match('/boundary="?(\S+)"?/', $contentType)) {
            if ($pos = strpos($contentType, ';')) {
                $this->SERVER['CONTENT_TYPE'] = substr($contentType, 0, $pos);
            } else {
                $this->SERVER['CONTENT_TYPE'] = $contentType;
            }
        } else {
            $this->SERVER['CONTENT_TYPE'] = 'multipart/form-data';
        }
    }

    public function getHeader($name, $default = null)
    {
        return $this->headers[$name] ?? $default;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getHost()
    {
        return $this->host;
    }

    public function getSchema()
    {
        return $this->schema;
    }

    public function getRequestUri()
    {
        return $this->requestUri;
    }

    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param bool $asResource
     * @return string|resource|null
     */
    public function getContent($asResource = false)
    {
        $body = $this->httpMessageParser->getBody();

        if ($body === null) {
            return $body;
        }

        return $asResource ? $body->getResource() : $body->toString();
    }

    public function GET()
    {
        return $this->GET;
    }

    public function POST()
    {
        return $this->POST;
    }

    public function COOKIE()
    {
        return $this->COOKIE;
    }

    public function FILES()
    {
        return $this->FILES;
    }

    public function SERVER()
    {
        return $this->SERVER;
    }
}