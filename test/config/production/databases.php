<?php


use QApi\Config\Database\PdoMysqlDatabase;

return [

    'default' => new PdoMysqlDatabase(
        host: '127.0.0.1', port: 3306, dbName: 'ad_xuechao_com', user: 'root', password: 'lunatic59247.'
    ),
    //    'default' => new \QApi\Config\Database\PdoSqlServDatabase(
    //        host: '139.196.108.45', port: 1433, dbName: 'BS3000+_003_2020', user: 'sa', password: 'lunatic59247.'
    //    ),
    //    'default' => new \QApi\Config\Database\MysqliDatabase(
    //        host: '127.0.0.1',
    //        port: 3306,
    //        dbName: 'ad_xuechao_com1',
    //        user: 'root',
    //        password: 'lunatic59247.'
    //    ),
];