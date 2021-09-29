<?php


namespace QApi\ORM;


use QApi\Logger;

class SQLLogger implements \Doctrine\DBAL\Logging\SQLLogger
{

    private float $time;

    public function startQuery($sql, ?array $params = null, ?array $types = null)
    {
        $this->time = microtime(true);
        Logger::sql($sql);
        if ($params) {
            Logger::sql('PARAMS:' . json_encode($params, JSON_THROW_ON_ERROR));
        }
        if ($types) {
            Logger::sql('TYPES:' . json_encode($types, JSON_THROW_ON_ERROR));
        }
    }

    public function stopQuery()
    {
        Logger::sql('RunTime:' . microtime(true) - $this->time . 'ms');
    }
}