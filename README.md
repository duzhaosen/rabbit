# RabbitMQ 的客户端
---
用法
----
1.向分发器发送消息

    $publisher = Publisher::with($config);
    $data = [
        'message'=>'hello world'
    ];
    $message = json_encode($data);
    $publisher->exchange('exchange_name')->publish($message,'consoles');
2.接收消息

        $consumer = \Gua\Consumer::with($config);
        $consumer->exchange('exchange_name')
            ->queue('console')->consume(function($message){
                echo $message->getBody();
                $message->ack();//确认消息处理成功
            },['topics_or_routes']);

*注意*  
* 队列的名称为空时，将创建一个带有随机名称，绑定到当前连接且非持久化的队列。这个队列将在连接关闭后自动删除。
* 默认的交换器的类型是 direct，需要在发送时指定路由键
* 消息必须在代码中显示确认，才会在队列中移除这条消息
* 若需要明确表示消息未处理可以使用 nack() 方法
* 默认入队消息和出队消息会记录 debug 日志，如需更改可在配置文件中全剧全局更改或在 attributes 参数中通过 log_level指定 制定

3.设置多hosts链接
在项目包的rabbit.php文件中设置多主机。合同项目的路径为：__APP__/config/rabbit.php
> 【1】将自己新增主机配置放置于services 数组中

配置如下：

```php
<?php

return [
    'connection'        => [
        'host'     => env('RABBIT_HOST', 'localhsot'),
        'port'     => env('RABBIT_PORT', 5672),
        'user'     => env('RABBIT_USER', 'guest'),
        'password' => env('RABBIT_PASSWORD', 'guest'),
        'vhost'    => '/',
        'heartbeat' => 60,//设置为0，则禁用心跳,单位:秒
    ],
    'default_log_level' => 'info',
    'exchange'          => [
        'name'        => '',
        'type'        => 'direct',
        'passive'     => false,
        'durable'     => true,
        'auto_delete' => false,
        'internal'    => false,
        'arguments'   => [],
    ],
    'queue'             => [
        'name'        => 'default',
        'passive'     => false,
        'durable'     => true,
        'exclusive'   => false,
        'auto_delete' => false,
        'arguments'   => [],
    ],
    //新增rabbit服务请使用，在次注册服务【1】
    'services' => [
         'common' => [
             'connection'        => [
                 'host'     => env('RABBIT_HOST_COMMON', 'localhost'),
                 'port'     => env('RABBIT_PORT_COMMON', 5672),
                 'user'     => env('RABBIT_USER_COMMON', 'haha'),
                 'password' => env('RABBIT_PASSWORD_COMMON', 'test'),
                 'heartbeat' => 60,//设置为0，则禁用心跳,单位:秒
                 'vhost'    => env('RABBIT_VHOST_COMMON', 'queue'),
             ],
             'default_log_level' => 'info',
             'exchange'          => [
                 'name'        => '',
                 'type'        => 'direct',
                 'passive'     => false,
                 'durable'     => true,
                 'auto_delete' => false,
                 'internal'    => false,
                 'arguments'   => [],
             ],
             'queue'             => [
                 'name'        => 'default',
                 'passive'     => false,
                 'durable'     => true,
                 'exclusive'   => false,
                 'auto_delete' => false,
                 'arguments'   => [],

             ],
         ]
    ]
];
```
### 使用 $serviceKey = "common";
            $abstractPublisher = 'rabbit.' . $serviceKey . '.publisher';
            $abstractConsumer  = 'rabbit.' . $serviceKey . '.consumer';
##TODO

* 增加多个物理主机的支持, 可借鉴 Laravel的数据库连接实现
* 优化连接的复用
* 优化多个 Vhost 的支持
* 增加对 Laravel 队列的支持
