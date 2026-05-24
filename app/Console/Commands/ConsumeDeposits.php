<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Kafka\KafkaMessageHandler;
use App\Kafka\Topics;
use Illuminate\Console\Command;
use Junges\Kafka\Facades\Kafka;

class ConsumeDeposits extends Command
{
    protected $signature = 'kafka:consume:deposits';

    protected $description = 'Consume bank.money.deposited events from Kafka';

    public function handle(): void
    {
        Kafka::consumer([Topics::MONEY_DEPOSITED])
            ->withConsumerGroupId(Topics::GROUP_DEPOSITS)
            ->withHandler(new KafkaMessageHandler(Topics::MONEY_DEPOSITED_DLQ))
            ->build()
            ->consume();
    }
}
