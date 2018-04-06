<?php


namespace Hbliang\SimpleTcpServer;


use Hbliang\SimpleTcpServer\Exceptions\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class HttpServer
{
    /**
     * @var ServerInterface
     */
    protected $server;

    /**
     * @var RouteCollection
     */
    protected $routes;

    public function __construct(ServerInterface $server)
    {
        $this->server = $server;
        $this->routes = new RouteCollection();
    }

    public function run()
    {
        $this->server->on('data', [$this, 'handleData']);

        $this->server->run();
    }

    public function handleData(ConnectionInterface $connection, $data)
    {
        try {
            $http = new Http($data);
            $http->parse();

            $urlMatcher = new UrlMatcher($this->routes, new RequestContext(
                '/',
                $http->getMethod(),
                $http->getHost(),
                $http->getSchema()
            ));

            try {
                $matches = $urlMatcher->match(parse_url($http->getRequestUri(), PHP_URL_PATH));
            } catch (\Throwable $e) {
                $exceptionMessage = $e->getMessage();
            }

            if (isset($matches['controller'])) {
                $response = $this->resolveController($matches['controller'], new Request(
                    $http->GET(),
                    $http->POST(),
                    [],
                    $http->COOKIE(),
                    $http->FILES(),
                    $http->SERVER(),
                    $http->getContent()
                ));
            } else {
                $response = new Response($exceptionMessage ?? 'Internal Server Error', 500);
            }

        } catch (BadRequestException $e) {
            $response = new Response($e->getMessage(), 400);
        }

        $rawText = $this->responseToHttpRawText($response);


        $connection->write($rawText);
        // TODO keep alive
        $connection->close();
    }

    /**
     * @param callable $controller
     * @return Response
     */
    protected function resolveController(callable $controller, Request $request)
    {
        $response = call_user_func($controller, $request);

        if ($response instanceof Response) {
            return $response;
        } elseif ((is_object($response) && !method_exists($response, '__toString')) || is_array($response)) {
            return new Response('Internal Server Error', 500);
        } else {
            return new Response((string)$response);
        }
    }

    /**
     * @param Response $response
     * @return string
     */
    protected function responseToHttpRawText(Response $response)
    {
        // status
        $text = sprintf('HTTP/%s %s %s', $response->getProtocolVersion(), $response->getStatusCode(), Response::$statusTexts[$response->getStatusCode()]) . "\r\n";

        // headers
        foreach ($response->headers->allPreserveCaseWithoutCookies() as $name => $values) {
            foreach ($values as $value) {
                $text .= "{$name}: {$value}" . "\r\n";
            }
        }

        // cookies
        foreach ($response->headers->getCookies() as $cookie) {
            $text .= 'Set-Cookie: ' . strval($cookie) . "\r\n";
        }

        // an empty line
        $text .= "\r\n";

        // message body
        $text .= $response->getContent();

        return $text;
    }

    public function addRoute($method, $uri, callable $controller)
    {
        $this->routes->add($uri, new Route($uri, ['controller' => $controller], [], [], "", [], $method));
    }

    public function get($uri, callable $controller)
    {
        $this->addRoute('GET', $uri, $controller);
        return $this;
    }

    public function post($uri, callable $controller)
    {
        $this->addRoute('POST', $uri, $controller);
        return $this;
    }

    public function delete($uri, callable $controller)
    {
        $this->addRoute('DELETE', $uri, $controller);
        return $this;
    }

    public function put($uri, callable $controller)
    {
        $this->addRoute('PUT', $uri, $controller);
        return $this;
    }
}