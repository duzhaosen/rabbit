<?php

namespace Gonghui\Queue;

use Prophecy\Exception\Doubler\MethodNotFoundException;


/**
 * Class Message
 * @package Gua
 * @method string getBody()
 */
class Message
{
    /**
     * 非持久化消息
     */
    const DELIVERY_MODE_NON_PERSISTENT = 1;
    /**
     * 持久化消息
     */
    const DELIVERY_MODE_PERSISTENT = 2;

    public function __construct(\AMQPEnvelope $message, \AMQPQueue $q = null)
    {
        $this->message = $message;
        $this->queue   = $q;
    }

    /**
     * Acknowledge the receipt of a message.
     *
     * This method allows the acknowledgement of a message that is retrieved
     * without the AMQP_AUTOACK flag through AMQPQueue::get() or
     * AMQPQueue::consume()
     * @return bool
     */
    public function ack()
    {
        return $this->queue->ack($this->message->getDeliveryTag());
    }

    /**
     * Mark a message as explicitly not acknowledged.
     *
     * Mark the message identified by delivery_tag as explicitly not
     * acknowledged. This method can only be called on messages that have not
     * yet been acknowledged, meaning that messages retrieved with by
     * AMQPQueue::consume() and AMQPQueue::get() and using the AMQP_AUTOACK
     * flag are not eligible. When called, the broker will immediately put the
     * message back onto the queue, instead of waiting until the connection is
     * closed. This method is only supported by the RabbitMQ broker. The
     * behavior of calling this method while connected to any other broker is
     * undefined.
     * @param bool $requeue 是否将消息再次放入队列，默认重新入队
     * @return bool
     */
    public function nack($requeue = true)
    {
        return $this->queue->nack($this->message->getDeliveryTag(), $requeue ? AMQP_REQUEUE : AMQP_NOPARAM);
    }

    public function drop()
    {
        return $this->nack(false);
    }

    /**
     * @param array $default
     * @return mixed
     */
    public function getData($default = [])
    {
        return json_decode($this->getBody(), true) ?: $default;
    }

    function __call($name, $arguments)
    {
        if (method_exists($this->message, $name)) {
            return call_user_func_array([$this->message, $name], $arguments);
        }
        if (method_exists($this->queue, $name)) {
            return call_user_func_array([$this->queue, $name], $arguments);
        }

        throw  new MethodNotFoundException('method not found', __CLASS__, $name, $arguments);
    }

}
