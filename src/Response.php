<?php


namespace QApi;


class Response
{
    private bool $status = true;
    private int $statusCode = 200;
    private mixed $data = [];
    private string $msg = 'Ok';
    private array $extra = [];
    // TODO
    private array $headers = [];
    private bool $raw = false;

    /**
     * Response constructor.
     * @param string|null $version
     */
    public function __construct(private ?string $version = null)
    {
        $app = Config::$app;
        if ($app && Router::$request) {
            if (in_array('*', $app->allowOrigin, true)) {
                $this->withHeader('Access-Control-Allow-Origin', $app->allowOrigin);
            } else if (in_array(Router::$request->getHost(), $app->allowOrigin, true)) {
                $this->withHeader('Access-Control-Allow-Origin', Router::$request->getHost());
            }
            $this->withHeader('Access-Control-Allow-Headers', implode(',', $app->allowHeaders));
        }
        $this->withHeader('x-powered-by', 'QApi');
        $this->withHeader('Content-Type', 'application/json;charset=utf-8');

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
     */
    public function setRaw($status = false): void
    {
        $this->raw = $status;
    }

    /**
     * @return bool
     */
    public function getStatus(): bool
    {
        return $this->status;
    }

    public function __toString(): string
    {
        if (!is_cli()) {
            foreach ($this->headers as $name => $header) {
                if (is_array($header)) {
                    header($name . ':' . implode(',', $header));
                } else {
                    header($name . ':' . $header);
                }
            }
        }
        if (!$this->raw) {
            $sendData = [
                'version' => $this->version ?? Config::version()->versionName,
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
        if ($sendData) {
            Logger::success("↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓  Response Data ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ");
            if (is_string($sendData)) {
                echo $sendData;
            } else {
                foreach ($this->headers as $name => $header) {
                    if (is_array($header)) {
                        header($name . ':' . implode(',', $header));
                    } else {
                        header($name . ':' . $header);
                    }
                }
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