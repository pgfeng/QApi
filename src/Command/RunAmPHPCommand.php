<?php

namespace QApi\Command;

class RunAmPHPCommand extends CommandHandler
{

    /**
     * @var string
     */
    public string $name = 'run:amphp';
    public function handler(array $argv): mixed
    {
        Amp\Loop::run(function () {
            $sockets = [
                Server::listen("0.0.0.0:1337"),
                Server::listen("[::]:1337"),
            ];

            $server = new HttpServer($sockets, new CallableRequestHandler(function (Request $request) {
                return new Response(Status::OK, [
                    "content-type" => "text/plain; charset=utf-8"
                ], "Hello, World!");
            }), new NullLogger);

            yield $server->start();

            // Stop the server gracefully when SIGINT is received.
            // This is technically optional, but it is best to call Server::stop().
            Amp\Loop::onSignal(SIGINT, function (string $watcherId) use ($server) {
                Amp\Loop::cancel($watcherId);
                yield $server->stop();
            });
        });
    }
}