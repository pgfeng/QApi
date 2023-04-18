<?php

namespace QApi\Validator;

abstract class Constraint
{
    abstract function validate(mixed $value);


}