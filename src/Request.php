<?php


namespace QApi;


use QApi\Http\Request\Methods;

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
     * @param Data|array|null $arguments
     * @param array|null $get
     * @param array|null $post
     * @param array|null $request
     * @param string|null $input
     * @param array|null $files
     * @param array|null $cookie
     * @param array|null $session
     * @param array|null $server
     * @param array|null $header
     * @param bool $init
     */
    public function __construct(public Data|null|array $arguments = null, array $get = null, array $post = null, array $request =
    null,
                                string                 $input = null, array $files = null,
                                array                  $cookie = null, array $session = null, array $server = null, array $header = null, bool $init = true)
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
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
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
            if (App::$app->injectionRunTime && !$this->server->has('REQUEST_TIME_FLOAT')) {
                $this->server->set('REQUEST_TIME_FLOAT', microtime(true));
            }
        }
    }

    public static function compileRequest($requestHeader, $requestBody)
    {
        $input = $requestBody;
        $server = [];
        $get = [];
        $post = [];
        $file = [];
        $cookie = [];
        $requestHeader = explode("\r\n", $requestHeader);
        $http = array_shift($requestHeader); //GET /favicon.ico HTTP/1.1
        $http = explode(' ', $http);
        if (count($http) != 3) {
            return new Request(init: true);
        }
        $server['REQUEST_METHOD'] = $http[0];
        $server['REQUEST_URI'] = $http[1];
        $server['SERVER_PROTOCOL'] = $http[2];
        foreach ($requestHeader as $item) {
            if (!$item) {
                break;
            }
            $item = trim($item);
            if (empty($item)) {
                continue;
            }
            $item = explode(':', $item, 2);
            $header[$item[0]] = $item[1];
        }
        foreach ($header as $key => $value) {
            $key = str_replace('-', '_', strtoupper($key));
            $server['HTTP_' . $key] = $value;
        }
        $getString = parse_url($server['REQUEST_URI'], PHP_URL_QUERY);
        if ($getString) {
            parse_str($getString, $get);
        }
        if ($server['REQUEST_METHOD'] == 'POST') {
            if (isset($header['Content-Type'])) {
                if ($header['Content-Type'] == 'application/x-www-form-urlencoded') {
                    parse_str($input, $post);
                } else if (stripos($header['Content-Type'], 'multipart/form-data')) {
                    // 处理文件上传
                    $boundary = substr($header['Content-Type'], strpos($header['Content-Type'], 'boundary=') + strlen('boundary='));
                    // 处理报文分割符和消息体内容
                    $bodyData = $requestBody;
                    $bodyData = explode("\r\n--{$boundary}\r\n", $bodyData);
                    // 获取上传文件的信息和内容
//                    print_r($bodyData);
                    foreach ($bodyData as $item) {
                        list($h, $content) = explode("\r\n\r\n", $item, 2);
                        preg_match('/name="(?P<name>[^"]+)"(?:; filename="(?P<filename>[^"]+)")?/',
                            $h,
                            $matches);
                        if (strpos($h, 'filename=') !== false) {
                            $name = trim($matches['name'], '"');
                            $filename = isset($matches['filename']) ? trim($matches['filename'], '"') : '';
                            // 将二进制数据存储到本地磁盘
                            $suffix = substr(strrchr($filename, '.'), 1);
                            $newFilename = time() . '_' . mt_rand(100, 999) . '.' . $suffix;
                            $tempDir = sys_get_temp_dir();
                            file_put_contents($tempDir . "{$newFilename}", $content);
                            // 添加到文件信息列表
                            $file[] = [
                                'name' => $name,
                                'filename' => $filename,
                                'tmp_filename' => $tempDir . "{$newFilename}"
                            ];
                        } else {
                            $post[$matches[1]] = substr($content, 0, -strlen("\r\n\r\n--{$boundary}--"));
                        }
                    }
                    echo "文件上传解析结果：" . json_encode(['post_data' => $post, 'file_info_list' => $file]) . "\n";

                }
            }
        }
        // cookie解析
        if (isset($header['Cookie'])) {
            $cookie = explode(';', $header['Cookie']);
            foreach ($cookie as $item) {
                list($key, $value) = explode('=', $item, 2);
                $cookie[trim($key)] = trim($value);
            }
        }
        return new self(get: $get, post: $post, input: $input, files: $file, cookie: $cookie, server: $server, header: $header, init: true);
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
    public function getScriptName(): string
    {
        return $this->server->get('SCRIPT_NAME', $this->server->get('ORIG_SCRIPT_NAME', ''));
    }

    public function getMethod(): string
    {
        return $this->method;
    }


    private function prepareRequestUri(): string
    {
        if (stripos($this->server->get('REQUEST_URI'), '?') !== false) {
            return $this->server->get('REQUEST_URI');
        } else {
            return $this->server->get('REQUEST_URI') . ($this->get->count() ? ('?' . http_build_query($this->get->toArray())) : '');
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
        $index = strpos($this->server->get('HTTP_HOST'), ':');
        if ($index !== false) {
            return substr($this->server->get('HTTP_HOST'), 0, $index);
        }
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
        $index = strpos($this->server->get('HTTP_HOST'), ':');
        if ($index !== false) {
            return substr($this->server->get('HTTP_HOST'), $index + 1);
        }
        return (int)$this->server->get('SERVER_PORT');
    }

    /**
     * @param $key
     * @param $default
     * @return array|string|null
     */
    public function get($key, $default = null): array|string|null
    {
        return $this->get->get($key, $default);
    }

    /**
     * @param $key
     * @param $default
     * @return array|string|null
     */
    public function post($key, $default = null): array|string|null
    {
        return $this->post->get($key, $default);
    }

    /**
     * @param $key
     * @param $default
     * @return array|string|null
     */
    public function header($key, $default = null): array|string|null
    {
        return $this->header->get($key, $default);
    }

    /**
     * @param $key
     * @param $default
     * @return array|string|null
     */
    public function session($key, $default = null): array|string|null
    {
        return $this->session->get($key, $default);
    }

    /**
     * @param $key
     * @param $default
     * @return array|string|null
     */
    public function cookie($key, $default = null): array|string|null
    {
        return $this->cookie->get($key, $default);
    }

    /**
     * @param $key
     * @param $default
     * @return array|string|null
     */
    public function file($key, $default = null): array|string|null
    {
        return $this->file->get($key, $default);
    }

    /**
     * @param $key
     * @param $default
     * @return array|string|null
     */
    public function request($key, $default = null): array|string|null
    {
        return $this->request->get($key, $default);
    }

    /**
     * @param $key
     * @param $default
     * @return array|string|null
     */
    public function server($key, $default = null): array|string|null
    {
        return $this->server->get($key, $default);
    }
}