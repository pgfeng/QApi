<?php

namespace QApi\Console;


class Command extends \Symfony\Component\Console\Command\Command
{
    public const SUCCESS = 0;
    public const FAILURE = 1;
    public const INVALID = 2;
}