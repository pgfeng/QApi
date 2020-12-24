<?php


namespace QApi;


class Response
{
    private bool $status = true;
    private int $statusCode = 200;
    private mixed $data = [];
    private string $message = 'Ok';
    private array $extra = [];

    /**
     * @param string $message
     * @return Response
     */
    public function setMessage(string $message): Response
    {
        $this->message = $message;
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

    public function ok(): Response
    {
        $this->status = true;
        return $this;
    }

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
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return bool
     */
    public function getStatus(): bool
    {
        return $this->status;
    }

    /**
     * @param mixed|null $sendData
     * @return mixed
     * @throws \ErrorException
     */
    public function send(mixed $sendData = null): void
    {
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
        } else {
            $sendData = [
                'version' => Config::version()->versionName,
                'code' => $this->statusCode,
                'status' => $this->status,
                'message' => $this->message,
                'data' => $this->data,
            ];
            $this->send(array_merge($sendData, $this->extra));
        }
    }
}