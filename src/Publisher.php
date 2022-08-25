<?php

namespace Gonghui\Queue;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Arr;

/**
 * Created by PhpStorm.
 * User: leo
 * Date: 16-3-31
 * Time: 上午11:19
 */
class Publisher extends AMQP
{
    /**
     * @param        $message string|array|Arrayable|Jsonable
     * @param string $routingKey
     * @param array  $attributes One of content_type, content_encoding,
     *                               message_id, user_id, app_id, delivery_mode,
     *                               priority, timestamp, expiration, type
     *                               or reply_to, headers.
     * @param int    $flags One or more of AMQP_MANDATORY and
     *                               AMQP_IMMEDIATE.
     * @return bool
     */
    public function publish($message, $routingKey = '', $attributes = [], $flags = AMQP_NOPARAM)
    {
        $this->initConnection();
        $exchange = $this->createOrGetExchange();

        if (!is_string($message)) {
            $message = collect($message)->toJson();
        }

        $context             = compact('message', 'attributes', 'flags', 'routingKey');
        $context['vhost']    = $this->vhost ?: '/';
        $context['exchange'] = $this->exchangeName;
        $this->log('enqueue data.', $attributes, $context);

        $ret = $exchange->publish($message, $routingKey, $flags, $attributes);
        $this->clearVhost();
        $this->log('publish result.', $attributes, compact('ret'));
        return $ret;
    }

    /**
     * 消息不会在系统重启后丢失
     * @param        $message
     * @param string $routingKey
     * @param array  $attributes
     * @return bool
     */
    public function durablePublish($message, $routingKey = '', $attributes = [])
    {
        //[
        //    'delivery_mode' => 2 //确保消息不会在系统重启后丢失
        //]
        $attributes = array_merge($attributes, Arr::get($this->config, 'message_attr', []));
        return $this->publish($message, $routingKey, $attributes);
    }
}
