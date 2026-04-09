<?php

declare(strict_types=1);

namespace Monoelf\Framework\kafka;

use RdKafka\Conf;
use RdKafka\Producer;

final class KafkaProducer
{
    private Producer $producer;

    public function __construct(string $brokers)
    {
        $conf = new Conf();

        $conf->set('bootstrap.servers', $brokers);
        $this->producer = new Producer($conf);
    }

    public function publish(string $topicName, string $key, string $message,): void
    {
        $topic = $this->producer->newTopic($topicName);

        $topic->produce(
            RD_KAFKA_PARTITION_UA,
            0,
            $message,
            $key
        );

        $this->producer->poll(0);

        $this->producer->flush(1000);
    }
}
