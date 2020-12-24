<?php
/**
 * Created by PhpStorm.
 * User: PGF
 * Date: 2017/7/0008
 * Time: 上午 11:32
 */

namespace QApi\Command;


use QApi\Command;

/**
 * @package QApi\Command
 */
abstract class CommandHandler
{
    protected Command $command;
    protected array $argv = [];

    /**
     * Handler constructor.
     * @param Command $command
     * @param array $argv
     */
    public function __construct(Command $command, $argv = [])
    {
        $this->command = $command;
    }

    /**
     * Handler名称
     * @var string
     */
    public string $name = '';

    /**
     * @param $argv
     * @return mixed
     */
    abstract public function handler($argv): mixed;

    /**
     * @return mixed
     */
    abstract public function help(): mixed;

}