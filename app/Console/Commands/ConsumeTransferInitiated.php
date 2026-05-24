<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Kafka\KafkaMessageHandler;
use App\Kafka\Topics;
use Illuminate\Console\Command;
use Junges\Kafka\Facades\Kafka;

class ConsumeTransferInitiated extends Command
{
    protected $signature = 'kafka:consume:transfer-initiated';

    protected $description = 'Consume bank.money.transfer.initiated events from Kafka';

    public function handle(): void
    {
        Kafka::consumer([Topics::MONEY_TRANSFER_INITIATED])
            ->withConsumerGroupId(Topics::GROUP_TRANSFERS)
            ->withHandler(new KafkaMessageHandler(Topics::MONEY_TRANSFER_INITIATED_DLQ))
            ->build()
            ->consume();
    }
}
