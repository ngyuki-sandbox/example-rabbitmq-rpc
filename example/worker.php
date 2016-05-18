<?php
namespace Example\RabbitMQ\Rpc\_;

require_once __DIR__ . '/../vendor/autoload.php';

use Parallel\Prefork;
use Example\RabbitMQ\Rpc\RpcListener;

function log($str)
{
    fprintf(STDERR, "[%05d] %s\n", getmypid(), $str);
}

$pp = new Prefork(array(
    'max_workers'  => 2,
    'trap_signals' => array(
        SIGHUP  => SIGTERM,
        SIGTERM => SIGTERM,
    ),
));

while ($pp->signalReceived() !== SIGTERM) {

    if ($pp->start()) {
        continue;
    }

    log("start process");

    $listener = new RpcListener();
    $listener->wait(function ($body, $done) {

        log("recv: $body");

        if (ctype_digit($body)) {
            $num = min((int)$body, 10);
            $done("sleep($num)");
            for ($i = $num; $i > 0; $i--) {
                sleep(1);
                log($i);
            }
            log("done");

            return null;
        } else {
            return strtoupper($body);
        }
    });

    $pp->finish();
}

$pp->waitAllChildren();
