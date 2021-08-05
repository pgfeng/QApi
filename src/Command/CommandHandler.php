<?php
/**
 * Created by PhpStorm.
 * User: PGF
 * Date: 2017/7/0008
 * Time: 上午 11:32
 */

namespace QApi\Command;


use JetBrains\PhpStorm\Pure;
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
     * @return bool
     */
    #[Pure] public function isWin(): bool
    {
        $index = stripos(PHP_OS, 'WIN');
        if ($index === false) {
            return false;
        }
        return $index === 0;
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
    abstract public function handler(array $argv): mixed;

    /**
     * @return mixed
     */
    abstract public function help(): mixed;

}