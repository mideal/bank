<?php

namespace App\Console\Commands;

use App\Contracts\DomainEvent;
use App\Kafka\Topics;
use App\Models\OutboxEvent;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Junges\Kafka\Facades\Kafka;
use Throwable;

class ProcessOutbox extends Command
{
    protected $signature = 'outbox:process';

    protected $description = 'Publish pending outbox events to Kafka';

    public function __construct(private readonly ConnectionInterface $db)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        OutboxEvent::pending()
            ->chunkById(100, function ($events) {
                foreach ($events as $outbox) {
                    $this->process($outbox);
                }
            });
    }

    private function process(OutboxEvent $outbox): void
    {
        $this->db->transaction(function () use ($outbox) {
            $locked = OutboxEvent::lockForUpdate()->find($outbox->id);

            if ($locked === null || $locked->status !== 'pending') {
                return;
            }

            $locked->incrementAttempts();

            try {
                $class = $locked->type;

                if (! class_exists($class) || ! is_a($class, DomainEvent::class, true)) {
                    throw new \RuntimeException("Unknown outbox event type: {$class}");
                }

                /** @var DomainEvent $event */
                $event = $class::fromPayload($locked->payload);

                Kafka::publish()
                    ->onTopic(Topics::forEvent($event))
                    ->withKafkaKey((string) $event->aggregateId())
                    ->withBodyKey('type', $class)
                    ->withBodyKey('payload', $locked->payload)
                    ->send();

                $locked->markProcessed();
            } catch (Throwable $e) {
                $locked->markFailed($e->getMessage());
            }
        });
    }
}
