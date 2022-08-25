<?php


namespace Gonghui\Queue;

use AMQPChannel;
use AMQPConnection;
use AMQPQueue;
use Psr\Log\LoggerInterface;
use Illuminate\Support\Arr;

class AMQP
{
    const VERSION = '0.3.8';
    /**
     * @var AMQPConnection
     */
    protected $connection;
    protected $createConnect = true;
    protected $config;
    /**
     * @var \AMQPChannel
     */
    protected $channel;
    /**
     * @var \AMQPQueue
     */
    protected $queue;
    /**
     * @var \AMQPExchange
     */
    protected $exchange;
    protected $exchangeName;
    private $exchangeFlags;
    //
    protected $queueName;
    private $queueFlags;
    //
    protected $vhost = null;
    //
    private $rebuild = true;
    /**
     * @var int $connectBuildAt 链接建立的时间
     */
    private $connectBuildAt;
    /**
     * @var LoggerInterface
     */
    protected $log;

    /**
     * Rabbit constructor.
     * @param LoggerInterface $log
     * @param array           $config
     * @internal param AMQPConnection $connection
     */
    public function __construct(LoggerInterface $log = null, $config = [])
    {
        $this->config = $config;
        $this->log    = $log;
    }

    public function vhost($vhost)
    {
        $this->vhost   = $vhost;
        $this->rebuild = true;
        return $this;
    }

    /**
     * @param     $name string 如果$name为''，只能创建非持久化的队列并且只能绑定到Connection，队列的名称在每次创建的时候随机生成。
     * @param int $flags
     * @return static
     */
    public function queue($name, $flags = AMQP_NOPARAM)
    {
        $tmpFlags = 0;
        if (Arr::get($this->config, 'queue.passive')) {
            $tmpFlags |= AMQP_PASSIVE;
        }
        if ($this->queueName === '' ? false : Arr::get($this->config, 'queue.durable')) {
            $tmpFlags |= AMQP_DURABLE;
        }
        if ($this->queueName === '' ? true : Arr::get($this->config, 'queue.exclusive')) {
            $tmpFlags |= AMQP_EXCLUSIVE;
        }
        if (Arr::get($this->config, 'queue.auto_delete')) {
            $tmpFlags |= AMQP_AUTODELETE;
        }
        $tmpFlags         |= $flags;
        $this->queueName  = $name;
        $this->queueFlags = $tmpFlags;
        return $this;
    }

    /**
     * @param string $exchangeName 默认为''，这个是默认的交换器，会绑定所有的队列，并且以队列的名称作为路由。
     * @param int    $flags
     * @return static
     * @throws AMQPQueueException
     */
    public function exchange($exchangeName = '', $flags = AMQP_NOPARAM)
    {
        if (is_null($exchangeName)) {
            throw new AMQPQueueException('exchange cannot be null');
        }

        $tmpFlags = 0;
        if (Arr::get($this->config, 'exchange.passive')) {
            $tmpFlags |= AMQP_EXCLUSIVE;
        }
        if (Arr::get($this->config, 'exchange.durable')) {
            $tmpFlags |= AMQP_DURABLE;
        }
        if (Arr::get($this->config, 'exchange.auto_delete')) {
            $tmpFlags |= AMQP_AUTODELETE;
        }
        if (Arr::get($this->config, 'exchange.internal')) {
            $tmpFlags |= AMQP_INTERNAL;
        }
        $tmpFlags            |= $flags;
        $this->exchangeName  = $exchangeName;
        $this->exchangeFlags = $tmpFlags;
        return $this;
    }

    public function close()
    {
        if (!is_null($this->connection)) {
            $this->connection->disconnect();
        }
    }

    function __destruct()
    {
        $this->close();
    }

    /**
     * @return AMQPChannel
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @return AMQPConnection
     */
    public function initConnection()
    {
        $this->checkHeartbeat();
        if (!$this->rebuild) {
            return $this->connection;
        }
        $connection = new AMQPConnection(Arr::get($this->config, 'connection'));
        //$connection->setHost(Arr::get($this->config, 'connection.host'));
        //$connection->setPort(Arr::get($this->config, 'connection.port'));
        $connection->setLogin(Arr::get($this->config, 'connection.user'));
        //$connection->setPassword(Arr::get($this->config, 'connection.password'));

        if (empty($this->vhost)) {
            $connection->setVhost(Arr::get($this->config, 'connection.vhost', '/'));
        } else {
            $connection->setVhost($this->vhost);
        }
        $connection->connect();
        $this->connection = $connection;
        $this->channel    = new AMQPChannel($connection);

        $this->rebuild        = false;
        $this->connectBuildAt = time();
        return $connection;
    }

    protected function clearVhost()
    {
        if (!is_null($this->connection) && !empty($this->vhost)) {
            $this->vhost(Arr::get($this->config, 'connection.vhost', '/'));
        }
    }

    protected function initExchange()
    {
        $exchange = $this->createOrGetExchange();
        $exchange->declareExchange();

        $this->exchange = $exchange;
        return $this->exchange;
    }

    public function initQueue()
    {
        if (!is_null($this->queue) && $this->queue->getChannel() === $this->channel) {
            return $this->queue;
        }
        if (is_null($this->queueName)) {
            throw new AMQPQueueException('queue cannot be null');
        }
        $queue = new AMQPQueue($this->channel);
        $queue->setFlags($this->queueFlags);
        $queue->setArguments(Arr::get($this->config, 'queue.arguments'));
        if (!empty($this->queueName)) {
            $queue->setName($this->queueName);
        }
        $queue->declareQueue();
        $this->queue = $queue;
        return $this->queue;
    }

    /**
     * @param $message
     * @param $attributes
     * @param $context
     */
    protected function log($message, $attributes, $context)
    {
        if (is_null($this->log)) {
            return;
        }
        $this->log->log(
            Arr::get($attributes, 'log_level', $this->config['default_log_level']),
            $message,
            $context
        );
    }

    /**
     * @return \AMQPExchange
     */
    protected function createOrGetExchange()
    {
        if (!is_null($this->exchange) &&
            $this->exchange->getChannel() === $this->channel &&
            $this->exchange->getName() == $this->exchangeName
        ) {
            return $this->exchange;
        }
        $exchange = new \AMQPExchange($this->channel);
        $exchange->setType(Arr::get($this->config, 'exchange.type'));
        $exchange->setName($this->exchangeName);
        $exchange->setFlags($this->exchangeFlags);
        $exchange->setArguments(Arr::get($this->config, 'exchange.arguments', []));
        return $exchange;
    }

    /**
     * 检查心跳值，到达心跳值时触发重新连接
     * rabbitmq-server默认心跳为60s为了避免配置错误导致心跳重置失败
     */
    private function checkHeartbeat()
    {
        if (!$this->rebuild && ($heartbeat = Arr::get($this->config, 'connection.heartbeat', 0)) > 0) {
            if ($heartbeat > 60) {
                $heartbeat = 60;
            }
            if ((time() - $this->connectBuildAt) > ($heartbeat - 2)) {
                $this->rebuild = true;
            }
        }
    }
}
