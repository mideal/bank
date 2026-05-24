<?php

declare(strict_types=1);

namespace App\Contracts;

interface DomainEvent
{
    public function toPayload(): array;

    public static function fromPayload(array $payload): static;

    public function aggregateId(): int;
}
