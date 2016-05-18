<?php
namespace Example\RabbitMQ\Rpc;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Channel\AMQPChannel;

class RpcListener
{
    /**
     * @var AMQPStreamConnection
     */
    private $connection;

    /**
     * @var AMQPChannel
     */
    private $channel;

    public function __construct()
    {
        $this->connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');

        $this->channel = $this->connection->channel();

        $this->channel->queue_declare('rpc_queue', false, false, false, false);

        $this->channel->basic_qos(null, 1, null);
    }

    public function __destruct()
    {
        $this->channel->close();
        $this->connection->close();
    }

    public function wait($callback)
    {
        $this->channel->basic_consume('rpc_queue', '', false, false, false, false,

            function (AMQPMessage $req) use ($callback) {

                $respond = false;

                $done = function ($result = null) use ($req, &$respond) {
                    if ($respond) {
                        return;
                    }
                    $respond = true;

                    $msg = new AMQPMessage($result, array(
                        'correlation_id' => $req->get('correlation_id'),
                    ));

                    /* @var $channel AMQPChannel */
                    $channel = $req->delivery_info['channel'];
                    $channel->basic_publish($msg, '', $req->get('reply_to'));
                    $channel->basic_ack($req->delivery_info['delivery_tag']);
                };

                $result = $callback($req->body, $done);
                $done($result);
            }
        );

        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }
}
