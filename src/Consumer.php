<?php

namespace Gonghui\Queue;


class Consumer extends AMQP
{

    public function consume(callable $callback, $topics = null, $attributes = null)
    {
        $this->initConnection();
        $this->initQueue();
        try {
            $exchangeName = $this->exchangeName;
            if (!empty($topics) && !empty($exchangeName)) {
                foreach ($topics as $binding_key) {
                    $this->queue->bind($exchangeName, $binding_key);
                }
            } elseif (!empty($exchangeName)) {
                $this->queue->bind($exchangeName);
            }
            $this->channel->qos(null, 1);
            $handler = function (\AMQPEnvelope $message, \AMQPQueue $queue) use ($callback, $attributes, $topics) {
                $msg = new Message($message, $queue);
                $this->log('consume data.', $attributes,
                    ['message' => $this->getProperties($message), 'queue' => $this->queueName, 'topics' => $topics]
                );

                if ($message->getHeader('X-Need-Restart') == 'UncleJin') {
                    $msg->ack();
                    throw  new AMQPRestartQueueException('重启队列');
                }

                $callback($msg);
            };
            $this->queue->consume($handler);
        } catch (\Exception $e) {
            throw $e;
        } finally {
            $this->close();
        }
    }

    /**
     * @param $message
     * @return array
     */
    protected function getProperties($message)
    {
        $result = [];

        $reflectionObject     = new \ReflectionObject($message);
        $reflectionProperties = $reflectionObject->getProperties();
        foreach ($reflectionProperties as $property) {
            $property->setAccessible(true);
            $result[$property->getName()] = $property->getValue($message);
        }
        return $result;
    }

}
