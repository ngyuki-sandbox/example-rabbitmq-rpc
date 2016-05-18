<?php
namespace Example\RabbitMQ\Rpc;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Channel\AMQPChannel;

class RpcClient
{
    /**
     * @var AMQPStreamConnection
     */
    private $connection;

    /**
     * @var AMQPChannel
     */
    private $channel;

    /**
     * @var string
     */
    private $callbackName;

    /**
     * @var callback
     */
    private $onResponse;

    public function __construct()
    {
        $this->connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');

        $this->channel = $this->connection->channel();

        $this->channel->queue_declare('rpc_queue', false, false, false, false);

        list($this->callbackName) = $this->channel->queue_declare("", false, false, true, false);

        $this->channel->basic_consume($this->callbackName, '', false, true, true, false,
            function (AMQPMessage $res) {
                if ($this->onResponse) {
                    $onResponse = $this->onResponse;
                    $onResponse($res);
                }
            }
        );
    }

    public function __destruct()
    {
        $this->channel->close();
        $this->connection->close();
    }

    public function call($body)
    {
        $correlationId = uniqid();

        $msg = new AMQPMessage($body, array(
            'reply_to' => $this->callbackName,
            'correlation_id' => $correlationId,
        ));

        $this->channel->basic_publish($msg, '', 'rpc_queue', true);

        $result = null;

        $this->onResponse = function (AMQPMessage $res) use ($correlationId, &$result) {
            if ($res->get('correlation_id') == $correlationId) {
                $result = (string)$res->body;
            }
        };

        while (count($this->channel->callbacks) && $result === null) {
            $this->channel->wait(null, false, 5);
        }

        return $result;
    }
}
