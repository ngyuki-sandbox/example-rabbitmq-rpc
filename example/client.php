<?php
namespace Example\RabbitMQ\Rpc\_;

require_once __DIR__ . '/../vendor/autoload.php';

use Example\RabbitMQ\Rpc\RpcClient;

function log($str)
{
    fprintf(STDERR, "[%05d] %s\n", getmypid(), $str);
}

$client = new RpcClient();
$data = implode(' ', array_slice($_SERVER['argv'], 1));
$res = $client->call($data);
log(var_export($res, true));
