<?php


use QApi\Config\Database\PdoMysqlDatabase;

return [
    'default' => new PdoMysqlDatabase(
        host: '127.0.0.1', port: 3306, dbName: 'test', user: 'root', password: '123456'
    ),
];