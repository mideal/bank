<?php

namespace App\Kafka;

use App\Contracts\DomainEvent;
use Illuminate\Support\Facades\Log;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\Handler;
use Junges\Kafka\Contracts\MessageConsumer;
use Junges\Kafka\Facades\Kafka;

final class KafkaMessageHandler implements Handler
{
    public function __construct(private readonly string $dlqTopic) {}

    public function __invoke(ConsumerMessage $message, MessageConsumer $_consumer): void
    {
        try {
            $this->process($message);
        } catch (\Throwable $e) {
            Log::error('Kafka message failed, routing to DLQ', [
                'dlq' => $this->dlqTopic,
                'topic' => $message->getTopicName(),
                'body' => $message->getBody(),
                'error' => $e->getMessage(),
            ]);

            Kafka::publish()
                ->onTopic($this->dlqTopic)
                ->withKafkaKey(\is_string($message->getKey()) ? $message->getKey() : '')
                ->withBodyKey('original_body', $message->getBody())
                ->withBodyKey('error', $e->getMessage())
                ->send();
        }
    }

    private function process(ConsumerMessage $message): void
    {
        $body = $message->getBody();

        if (! \is_array($body)) {
            throw new \UnexpectedValueException('Kafka message body must be an array');
        }

        $type = $body['type'] ?? null;
        $payload = $body['payload'] ?? [];

        if (! \is_string($type)) {
            throw new \UnexpectedValueException('Kafka message type must be a string');
        }

        if (! class_exists($type) || ! is_a($type, DomainEvent::class, true)) {
            throw new \RuntimeException('Unknown or invalid event type: '.$type);
        }

        if (! \is_array($payload)) {
            throw new \UnexpectedValueException('Kafka message payload must be an array');
        }

        $event = $type::fromPayload($payload);

        event($event);
    }
}
