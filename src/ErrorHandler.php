<?php


namespace QApi;


use Whoops\Exception\Formatter;
use Whoops\Handler\Handler;
use Whoops\Handler\JsonResponseHandler;

class ErrorHandler extends JsonResponseHandler
{

    /**
     * @return int
     */
    public function handle(): int
    {

        $response = [
            'code' => 500,
            'status' => false,
            'error' => Formatter::formatExceptionAsDataArray(
                $this->getInspector(),
                $this->addTraceToOutput(),
            ),
            'error_context'=> debug_backtrace(),
        ];
        echo json_encode($response, JSON_THROW_ON_ERROR | defined('JSON_PARTIAL_OUTPUT_ON_ERROR') ? JSON_PARTIAL_OUTPUT_ON_ERROR : 0);
        error_log(json_encode($response));
        return Handler::LAST_HANDLER;
    }
}