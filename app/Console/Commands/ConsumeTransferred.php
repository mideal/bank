<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Kafka\KafkaMessageHandler;
use App\Kafka\Topics;
use Illuminate\Console\Command;
use Junges\Kafka\Facades\Kafka;

class ConsumeTransferred extends Command
{
    protected $signature = 'kafka:consume:transferred';

    protected $description = 'Consume bank.money.transferred events from Kafka';

    public function handle(): void
    {
        Kafka::consumer([Topics::MONEY_TRANSFERRED])
            ->withConsumerGroupId(Topics::GROUP_TRANSFERRED)
            ->withHandler(new KafkaMessageHandler(Topics::MONEY_TRANSFERRED_DLQ))
            ->build()
            ->consume();
    }
}
