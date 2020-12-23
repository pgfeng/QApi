<?php


use QApi\Config\Database\PdoMysqlDatabase;

return [
    'default' => new PdoMysqlDatabase(
        host: '127.0.0.1', port: 3306, dbName: 'ad_xuechao_com', user: 'root', password: 'lunatic59247.'
    ),
];