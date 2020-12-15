<?php


namespace QApi;


use QApi\Http\Request\Methods;

/**
 * Class Request
 * @package GFPHP\Http
 */
class Request
{
    use Methods;

    /**
     * 头部信息
     * @var Data
     */
    public Data $header;

    /**
     * Server信息
     * @var Data
     */
    public Data $server;

    /**
     * Get参数
     * @var Data
     */
    public Data $get;

    /**
     * Post参数
     * @var Data
     */
    public Data $post;

    /**
     * Request参数
     * @var Data
     */
    public Data $request;

    /**
     * File上传数据
     * @var Data
     */
    public Data $file;

    /**
     * Cookie数据
     * @var Data
     */
    public Data $cookie;

    /**
     * Session数据
     * @var Data
     */
    public Data $session;

    /**
     * 请求类型
     * @var string
     */
    public string $method;

    /**
     * 真实请求地址 携带参数
     * @var null | string
     */
    public ?string $requestUri = null;

    /**
     * 路由地址 不携带参数
     * @var null|string
     */
    public ?string $routeUri = null;

    /**
     * 初始化
     * @param Data $arguments
     */
    public function __construct(public Data $arguments)
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (str_starts_with($name, 'HTTP_')) {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        $this->header = new Data($headers);
        $this->server = new Data($_SERVER);
        $this->routeUri = substr($_SERVER['PATH_INFO'] ?? '', 1) ?? isset($_GET['_router']);
        unset($_GET['_router'], $_REQUEST['_router']);
        $this->get = new Data($_GET);
        $this->post = new Data($_POST);
        $this->request = new Data($_REQUEST);
        $this->file = new Data($_FILES);
        $this->cookie = new Data($_COOKIE);
        $_SESSION = $_SESSION ?? [];
        $this->session = new Data($_SESSION);
        $this->method = strtoupper($this->server->get('REQUEST_METHOD'));
        $this->requestUri = $this->prepareRequestUri();
    }

    /**
     * Returns current script name.
     *
     * @return string
     */
    public function getScriptName(): string
    {
        return $this->server->get('SCRIPT_NAME', $this->server->get('ORIG_SCRIPT_NAME', ''));
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * 获取客户端IP地址
     * @return string
     */
    public function getClientIp(): string
    {
        if ($this->server) {

            $ip_address = $this->server["HTTP_X_FORWARDED_FOR"] ?? $this->server["HTTP_CLIENT_IP"] ?? $this->server["REMOTE_ADDR"];

        } else if (getenv("HTTP_X_FORWARDED_FOR")) {

            $ip_address = getenv("HTTP_X_FORWARDED_FOR");

        } else if (getenv("HTTP_CLIENT_IP")) {

            $ip_address = getenv("HTTP_CLIENT_IP");

        } else {

            $ip_address = getenv("REMOTE_ADDR");

        }

        return preg_match('/[\d\.]{7,15}/', $ip_address, $matches) ? $matches [0] : '';
    }

    private function prepareRequestUri()
    {
        $requestUri = '';

        if ('1' === $this->server->get('IIS_WasUrlRewritten') && '' !== $this->server->get('UNENCODED_URL')) {
            // IIS7 with URL Rewrite: make sure we get the unencoded URL (double slash problem)
            $requestUri = $this->server->get('UNENCODED_URL');
            $this->server->remove('UNENCODED_URL');
            $this->server->remove('IIS_WasUrlRewritten');
        } elseif ($this->server->has('REQUEST_URI')) {
            $requestUri = $this->server->get('REQUEST_URI');

            if ('' !== $requestUri && str_starts_with($requestUri, '/')) {
                // To only use path and query remove the fragment.
                if (false !== $pos = strpos($requestUri, '#')) {
                    $requestUri = substr($requestUri, 0, $pos);
                }
            } else {
                // HTTP proxy reqs setup request URI with scheme and host [and port] + the URL path,
                // only use URL path.
                $uriComponents = parse_url($requestUri);

                if (isset($uriComponents['path'])) {
                    $requestUri = $uriComponents['path'];
                }

                if (isset($uriComponents['query'])) {
                    $requestUri .= '?' . $uriComponents['query'];
                }
            }
        } elseif ($this->server->has('ORIG_PATH_INFO')) {
            // IIS 5.0, PHP as CGI
            $requestUri = $this->server->get('ORIG_PATH_INFO');
            if ('' !== $this->server->get('QUERY_STRING')) {
                $requestUri .= '?' . $this->server->get('QUERY_STRING');
            }
            $this->server->remove('ORIG_PATH_INFO');
        }

        // normalize the request URI to ease creating sub-requests from this request
        $this->server->set('REQUEST_URI', $requestUri);

        return $requestUri;
    }


    /**
     * @return bool
     */
    public function isXmlHttpRequest(): bool
    {
        return 'XMLHttpRequest' === $this->header->get('X-Requested-With');
    }

    /**
     * 是否是Ajax请求
     * @return bool
     */
    public function isAjaxHttpRequest(): bool
    {
        return $this->server->has('HTTP_X_REQUESTED_WITH') && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * @return string
     */
    public function domain(): string
    {
        return $this->getScheme() . '://' . $this->getHttpHost();
    }

    /**
     * @return bool
     */
    private function isSecure(): bool
    {
        $https = $this->server->get('HTTPS');

        return !empty($https) && 'off' !== strtolower($https);
    }

    /**
     * @return string|null
     */
    private function getHttpHost(): ?string
    {
        $scheme = $this->getScheme();
        $port = $this->getPort();

        if (('http' === $scheme && 80 === $port) || ('https' === $scheme && 443 === $port)) {
            return $this->getHost();
        }

        return $this->getHost() . ':' . $port;
    }

    /**
     * 获取Host
     * @return string|null
     */
    public function getHost(): ?string
    {
        return $this->server->get('HTTP_HOST');
    }

    /**
     * 获取当前完整网址
     */
    public function currentUrl(): string
    {
        return $this->domain() . $this->requestUri;
    }

    /**
     * @return string
     */
    public function getScheme(): string
    {
        return $this->isSecure() ? 'https' : 'http';
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return (int)$this->server->get('SERVER_PORT');
    }
}