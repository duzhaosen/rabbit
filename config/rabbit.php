<?php

return [
    'connection' => [
        'host'      => 'rabbitmq',
        'port'      => 5672,
        'user'      => 'guest',
        'password'  => 'guest',
        'heartbeat' => 60,//设置为0，则禁用心跳,单位:秒
        'vhost'     => '/',
    ],

    'exchange'          => [
        'type'        => 'direct',
        'passive'     => false,
        'durable'     => true,
        'auto_delete' => false,
        'internal'    => false,
        'arguments'   => [],
    ],
    'queue'             => [
        'passive'     => false,
        'durable'     => true,
        'exclusive'   => false,//是否绑定到Connection
        'auto_delete' => false,
        'arguments'   => [],
    ],
    'default_log_level' => 'debug',
    'message_attr'      => [
        'delivery_mode' => 2,
    ],
];
