<?php


namespace QApi;


use QApi\Cache\Cache;
use QApi\Http\Request\Methods;
use QApi\Http\Request\MethodsEnum;

/**
 * Class Request
 * @package QApi
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
     * 用户信息
     */
    public Data|null $auth = null;

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
     * @var string|null
     */
    public ?string $input;

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
     * @param Data|null $arguments
     * @param array|null $get
     * @param array|null $post
     * @param array|null $request
     * @param string|null $input
     * @param array|null $files
     * @param array|null $cookie
     * @param array|null $session
     * @param array|null $server
     * @param array|null $header
     */
    public function __construct(public Data|null|array $arguments = null, array $get = null, array $post = null, array $request =
    null,
                                string                 $input = null, array $files = null,
                                array                  $cookie = null, array $session = null, array $server = null, array $header = null, bool $init = true, bool $log = true)
    {
        if ($init) {
            if (!$this->arguments) {
                $params = [];
                $this->arguments = new Data($params);
            } elseif (is_array($arguments)) {
                $this->arguments = new Data($arguments);
            }
            $_SESSION = $_SESSION ?? [];
            $_GET = $get ?? $_GET;
            $_POST = $post ?? $_POST;
            $_REQUEST = $request ?? $_REQUEST;
            $_FILES = $files ?? $_FILES;
            $input = $input ?? file_get_contents('php://input');
            $_COOKIE = $cookie ?? $_COOKIE;
            $_SESSION = $session ?? $_SESSION;
            $_SERVER = $server ?? $_SERVER;
            $headers = $header ?? [];
            foreach ($_SERVER as $name => $value) {
                if (str_starts_with($name, 'HTTP_')) {
                    $headers[strtolower(substr($name, 5))] = $value;
                }
            }
            $this->header = new Data($headers);
            $this->server = new Data($_SERVER);
            if (isset($_GET['_router'])) {
                $this->routeUri = $_GET['_router'];
            } else {
                $this->routeUri = substr($_SERVER['PATH_INFO'] ?? '', 1);
            }
            unset($_GET['_router'], $_REQUEST['_router']);
            $this->get = new Data($_GET);
            $this->post = new Data($_POST);
            $this->request = new Data($_REQUEST);
            $this->file = new Data($_FILES);
            $this->cookie = new Data($_COOKIE);
            $this->input = $input;
            $this->session = new Data($_SESSION);
            $this->method = strtoupper($this->server->get('REQUEST_METHOD'));
            $this->requestUri = $this->prepareRequestUri();
            if ($log) {
                Logger::info(' RouterStart' . ' -> ' . $this->getScheme() . '://' . $this->getHost() .
                    $this->requestUri);
                Logger::info("↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓  Request Data ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ");
                Logger::info(' RequestMethod' . ' -> ' . $this->method);
                Logger::info(' HeaderData -> ' . $this->header);
                if ($this->method === MethodsEnum::METHOD_POST) {
                    Logger::info(' PostData -> ' . $this->post);
                    if ($this->file->count()) {
                        Logger::info(' FileData -> ' . $this->file);
                    }
                    if ($this->input) {
                        Logger::info(' InputData -> ' . $this->input);
                    }
                }
                Logger::info("↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑  Request Data  ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ");
            }
        }
    }

    /**
     * @return string
     */
    function getClientIp(): string
    {
        $ali = $this->header->get('ali-cdn-real-ip');
        if ($ali) {
            return $ali;
        }
        $cdn = $this->header->get('cdn-src-ip');
        if ($cdn) {
            return $cdn;
        }
        $forwarded = $this->header->get('x-forwarded-for');
        if (!$forwarded) {
            if ($ip = $this->header->get('x-real-ip')) {
                return $ip;
            }
            if ($ip = $this->server->get('HTTP_CLIENT_IP')) {
                return $ip;
            }
            if ($ip = $this->server->get('REMOTE_ADDR')) {
                return $ip;
            }
        }
        $forwarded = explode(',', $forwarded);
        $temp = $forwarded;
        for ($i = 0; $i < count($forwarded); $i++) {
            $forwarded[$i] = trim($forwarded[$i]);
            if (preg_match('/^(10|172|192)(.*)$/', $forwarded[$i])) {
                unset($temp[$i]);
            }
        }
        if (empty($temp)) {
            return end($forwarded);
        } else {
            return end($temp);
        }
    }

    public static function getInstance(): self
    {
        return new self(new Data(), [], [], [], null, [], [], [], [], null, false, false);
    }

    /**
     * Returns current script name.
     *
     * @return string
     */
    public
    function getScriptName(): string
    {
        return $this->server->get('SCRIPT_NAME', $this->server->get('ORIG_SCRIPT_NAME', ''));
    }

    public
    function getMethod(): string
    {
        return $this->method;
    }


    private
    function prepareRequestUri()
    {
        if (stripos($this->server->get('REQUEST_URI'), '?') !== false) {
            return $this->server->get('REQUEST_URI');
        } else {
            return $this->server->get('REQUEST_URI') . ($this->server->get('QUERY_STRING') ? '?' . $this->server['QUERY_STRING'] : '');
        }
    }


    /**
     * @return bool
     */
    public
    function isXmlHttpRequest(): bool
    {
        return 'XMLHttpRequest' === $this->header->get('X-Requested-With');
    }

    /**
     * 是否是Ajax请求
     * @return bool
     */
    public
    function isAjaxHttpRequest(): bool
    {
        return $this->server->has('HTTP_X_REQUESTED_WITH') && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * @return string
     */
    public
    function domain(): string
    {
        return $this->getScheme() . '://' . $this->getHttpHost();
    }

    /**
     * @return bool
     */
    private
    function isSecure(): bool
    {
        $https = $this->server->get('HTTPS');

        return !empty($https) && 'off' !== strtolower($https);
    }

    /**
     * @return string|null
     */
    private
    function getHttpHost(): ?string
    {
        $scheme = $this->getScheme();
        $port = (int)$this->getPort();
        $host = $this->getHost();

        if (('http' === $scheme && 80 === $port) || ('https' === $scheme && 443 === $port)) {
            return $host;
        }

        if (str_contains($host, ':')) {
            return $host;
        }
        return $this->getHost() . ':' . $port;
    }

    /**
     * 获取Host
     * @return string|null
     */
    public
    function getHost(): ?string
    {
        return $this->server->get('HTTP_HOST');
    }

    /**
     * 获取当前完整网址
     */
    public
    function currentUrl(): string
    {
        return $this->domain() . $this->requestUri;
    }

    /**
     * @return string
     */
    public
    function getScheme(): string
    {
        return $this->isSecure() ? 'https' : 'http';
    }

    /**
     * @return int
     */
    public
    function getPort(): int
    {
        return (int)$this->server->get('SERVER_PORT');
    }
}