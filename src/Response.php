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

    /**
     * Response constructor.
     * @param string|null $version
     */
    public function __construct(private ?string $version = null)
    {
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
     * @return Response
     */
    public function setExtra(array $extra): Response
    {
        $this->extra = $extra;
        return $this;
    }

    /**
     * @return $this
     */
    public function ok(): Response
    {
        $this->status = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function fail(): Response
    {
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
     * @return bool
     */
    public function getStatus(): bool
    {
        return $this->status;
    }

    public function __toString(): string
    {

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
                if (PHP_SAPI !== 'cli') {
                    header('Content-Type:application/json');
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