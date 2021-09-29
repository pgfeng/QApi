<?php


namespace QApi\ORM\Connector;


use Doctrine\DBAL\Connection;

interface ConnectorInterface
{

    /**
     * @param mixed $config
     * @return Connection
     */
    public function getConnector(mixed $config): Connection;
}