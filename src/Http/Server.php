<?php

namespace QApi\Http;

use QApi\Request;
use QApi\Response;
use QApi\Router;

class Server
{
    private \Socket|false $socket = false;

    public function __construct($port = 9501, public string $documentRoot = __DIR__)
    {
        $socket = $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (false === $socket) {
            throw new \Exception('socket_create() failed: reason: ' . socket_strerror(socket_last_error()));
        }
        socket_set_option($socket, SOL_SOCKET, SO_REUSEPORT, 1);
        socket_bind($socket, '0.0.0.0', $port);
        socket_listen($this->socket);
    }

    public function handle(callable $callback)
    {
        while ($client = socket_accept($this->socket)) {
            $requestBody = $this->socketRead($client);
            $request = Router::$request = Request::compileRequest(...$requestBody);
            $path = $this->documentRoot . trim($request->requestUri, '/');
            if (str_contains($path, '?')) {
                $path = substr($path, 0, strpos($path, '?'));
            }
            if ($path && file_exists($path) && is_readable($path)) {
                $response = new Response();
                $response->setReason('OK');
                $response->withHeader('Content-Type', mime_content_type($path));
                // CacheControl
                $response->withHeader('Cache-Control', 'max-age=31536000');
                $response->withHeader('Expires', gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
                $response->withHeader('Pragma', 'cache');
                // Etag
                $cacheMd5 = md5_file($path);
                $response->withHeader('Etag', $cacheMd5);
                if (trim($request->server->get('HTTP_IF_NONE_MATCH','')) == $cacheMd5) {
                    $response->setCode(304);
                    $response->setReason('Not Modified');
                } else {
                    $response->withHeader('Content-Length', filesize($path));
                    $response->setData(file_get_contents($path));
                    $response->setRaw(true);
                }
            } else {
                $response = $callback($request, new Response());
            }
            if (!$response instanceof Response) {
                $response = new Response();
                $response->setData($response)->setRaw(true);
            }
            $res = "HTTP/1.1 " . $response->getStatusCode() . " " . $response->getReason() . "\r\n";
            foreach ($response->getHeaders() as $key => $value) {
                $res .= $key . ': ' . implode(',', $value) . "\r\n";
            }
            $body = (string)$response;
            $res .= "Content-Length: " . strlen($body) . "\r\n\r\n";
            $res .= $body;
            socket_write($client, $res, strlen($res));
            socket_close($client);
        }
    }

    private function socketRead($client): array
    {
        socket_set_option($client, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 5, 'usec' => 10));
        $requestHeader = '';
        $requestBody = '';
        while (false !== ($buf = socket_read($client, 1024))) {
            $requestHeader .= $buf;
            if (str_contains($requestHeader, "\r\n\r\n")) {
                $data = explode("\r\n\r\n", $requestHeader);
                if (count($data) > 2) {
                    $requestHeader = $data[0];
                    $requestBody = $data[1];
                }
                break;
            }
        }
        $contentLength = 0;
        if (preg_match("/Content-Length:\s*(\d+)/", $requestHeader, $matches)) {
            $contentLength = (int)$matches[1];
        }
        while (strlen($requestBody) < $contentLength) {
            $buf = socket_read($client, 8);
            if (false === $buf || '' === $buf) {
                break;
            }
            $requestBody .= $buf;
        }
        return [$requestHeader, $requestBody];
    }

    public function __destruct()
    {
        if ($this->socket) {
            socket_close($this->socket);
        }
    }
}
