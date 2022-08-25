<?php

require dirname(__DIR__) . '/vendor/autoload.php';

class PublisherGuaziRabbitTest extends PHPUnit_Framework_TestCase
{

    private $config;

    const EXCHANGE = 'test';

    public function setUp()
    {
        $this->config = include dirname(__DIR__) . '/config/rabbit.php';
    }

    public function testCreatePublisher()
    {
        $t0 = microtime(true);

        $t1        = microtime(true);
        $publisher = new \Gonghui\Queue\Publisher($this->config);
        $publisher->vhost('/test');
        $publisher->exchange(self::EXCHANGE);
        for ($i = 0; $i < 100000; $i++) {
            $data    = [
                'message' => 'hello world' . $i,
            ];
            $message = json_encode($data);
            $publisher->durablePublish($message, 'payment_ok');
        }
        var_dump([
            microtime(true) - $t0,
            $t1 - $t0,
        ]);

    }

    public function testManyExchanges()
    {
        $publisher = new \Gonghui\Queue\Publisher(null, $this->config);
        $publisher->vhost('/test');
        $publisher->exchange('exchange1');
        $publisher->durablePublish([
            'a' => time(),
        ], 'test');

        $publisher->exchange('exchange2');
        $publisher->durablePublish([
            'b' => time(),
        ], 'test');
    }

    public function testPublishToDefaultExchange()
    {
        $publisher = new \Gonghui\Queue\Publisher(null, $this->config);

        $publisher->vhost('/plouto');
        $publisher->exchange('plouto.delayed');
        $publisher->durablePublish([
            'a' => time(),
        ], 'transfer');
    }

}
