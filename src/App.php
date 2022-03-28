<?php


namespace QApi;


class Response
{

    /** @var array Response code */
    private static $phrases = array(
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-status',
        208 => 'Already Reported',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        511 => 'Network Authentication Required',
    );
    private bool $status = true;
    private int $statusCode = 200;
    private mixed $data = [];
    private string $msg = 'Ok';
    private array $extra = [];
    /**
     * custom HTTP response phrase
     * @var string|null
     */
    private ?string $reason = null;

    // TODO
    private array $headers = [];
    private bool $raw = false;

    /**
     * Response constructor.
     * @param string|null $version
     */
    public function __construct(private ?string $version = '1.1')
    {
        $app = Config::$app;
        if ($app && Router::$request) {
            if (in_array('*', $app->allowOrigin, true)) {
                $this->withHeader('Access-Control-Allow-Origin', $app->allowOrigin);
            } else if (in_array(Router::$request->getHost(), $app->allowOrigin, true)) {
                $this->withHeader('Access-Control-Allow-Origin', Router::$request->getHost());
            }
            $app->allowHeaders[] = '_QApi';
            $this->withHeader('Access-Control-Allow-Headers', implode(',', $app->allowHeaders));
        }
        $this->withHeader('X-Powered-By', 'QApi');
        $this->withHeader('Content-Type', 'application/json;charset=utf-8');

    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @param string $version
     * @return Response
     */
    public function setVersion(string $version = '1.1'): Response
    {
        $this->version = $version;
        return $this;
    }

    /**
     * @param string|null $reason
     * @return Response
     */
    public function setReason(string $reason = null): Response
    {
        $this->reason = $reason;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getReason(): ?string
    {
        return $this->reason ?: (self::$phrases[$this->statusCode] ?? '');
    }

    /**
     * @param string $name
     * @param string|array $value
     * @return $this
     */
    public function withHeader(string $name, string|array $value): Response
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasHeader(string $name): bool
    {
        return isset($this->headers[$name]);
    }

    /**
     * @param string $name
     * @return $this
     */
    public function removeHeader(string $name): Response
    {
        unset($this->headers[$name]);
        return $this;
    }

    /**
     * @param string $name
     * @return array|string
     */
    public function getHeader(string $name): array|string
    {
        return $this->headers[$name] ?? '';
    }

    /**
     * @param string $msg
     * @return Response
     */
    public function setMsg(string $msg): Response
    {
        $this->msg = $msg;
        return $this;
    }

    /**
     * @param mixed $data
     * @return $this
     */
    public function setData(mixed $data): Response
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @param int $code
     * @return $this
     */
    public function setCode(int $code): Response
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * @param array $extra
     * @param bool $merge
     * @return Response
     */
    public function setExtra(array $extra, $merge = true): Response
    {
        if ($merge) {
            $this->extra = array_merge($this->extra, $extra);
        } else {
            $this->extra = $extra;
        }
        return $this;
    }

    /**
     * @param string|null $msg
     * @return $this
     */
    public function ok(?string $msg = null): Response
    {
        if ($msg) {
            $this->setMsg($msg);
        }
        $this->status = true;
        return $this;
    }

    /**
     * @param string|null $msg
     * @return $this
     */
    public function fail(?string $msg = null): Response
    {
        if ($msg) {
            $this->setMsg($msg);
        }
        $this->status = false;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getData(): mixed
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function getExtra(): array
    {
        return $this->extra;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return string
     */
    public function getMsg(): string
    {
        return $this->msg;
    }

    /**
     * @param false $status
     * @return Response
     */
    public function setRaw($status = true): Response
    {
        $this->raw = $status;
        return $this;
    }

    /**
     * @return bool
     */
    public function getStatus(): bool
    {
        return $this->status;
    }

    private function setHeader(): void
    {
        header('HTTP/' . $this->version . ' ' . $this->statusCode . ' ' . ($this->reason ?:
                (self::$phrases[$this->statusCode] ??
                    '')));
        foreach ($this->headers as $name => $header) {
            if (is_array($header)) {
                header($name . ':' . implode(',', $header));
            } else {
                header($name . ':' . $header);
            }
        }
    }

    public function __toString(): string
    {
        if (!is_cli()) {
            $this->setHeader();
        }
        if (!$this->raw) {
            $sendData = [
                'version' => Config::version()->versionName,
                'code' => $this->statusCode,
                'status' => $this->status,
                'msg' => $this->msg,
                'data' => $this->data,
            ];
            Logger::success("↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓  Response Data ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ");
            Logger::success($sendData);
            Logger::success("↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑  Response Data ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ");
            return json_encode(array_merge($sendData, $this->extra), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        }
        return $this->data;
    }

    /**
     * @param mixed|null $sendData
     * @return mixed
     */
    public function send(mixed $sendData = null): void
    {
        if (!is_cli()) {
            $this->setHeader();
        }
        if ($sendData) {
            Logger::success("↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓  Response Data ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ");
            if (is_string($sendData)) {
                echo $sendData;
            } else {
                $responseData = new Data($sendData);
                echo $responseData;
            }
            Logger::success($sendData);
            Logger::success("↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑  Response Data ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ");
            exit;
        }

        $sendData = [
            'version' => Config::version()->versionName,
            'code' => $this->statusCode,
            'status' => $this->status,
            'msg' => $this->msg,
            'data' => $this->data,
        ];
        $this->send(array_merge($sendData, $this->extra));
    }
}