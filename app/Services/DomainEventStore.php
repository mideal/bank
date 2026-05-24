<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\DomainEvent;
use App\Repositories\OutboxRepository;

final class DomainEventStore
{
    public function __construct(private readonly OutboxRepository $outboxRepository) {}

    public function store(DomainEvent $event): void
    {
        $this->outboxRepository->store($event);
    }
}
