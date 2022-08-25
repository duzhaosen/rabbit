<?php


require dirname(__DIR__) . '/vendor/autoload.php';
class ConsumerGuaziRabbitTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var \Gonghui\Queue\Consumer
     */
    private $consumer;
    private $config;

    public function setUp()
    {
        $this->config   = include dirname(__DIR__) . '/config/rabbit.php';
        $this->consumer = \Gonghui\Queue\Consumer::with($this->config);
    }

    public function testConsumerWithException()
    {
        try {
            $consumer = $this->consumer
                ->vhost('/test')
                ->exchange('test')
                ->queue('test');
            $consumer
                ->consume(function (\Gonghui\Queue\Message $message) {
//                    echo $message->getBody();
                    $message->ack();
                }, ['payment_ok']);
        } catch (CustomException $e) {
            $this->assertTrue(true);
        }


    }
}

class  CustomException extends Exception
{

}
