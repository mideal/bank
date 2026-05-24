<?php

namespace App\Repositories;

use App\Contracts\DomainEvent;
use App\Models\OutboxEvent;

class OutboxRepository
{
    public function store(DomainEvent $event): OutboxEvent
    {
        $outbox = new OutboxEvent;
        $outbox->type = $event::class;
        $outbox->payload = $event->toPayload();
        $outbox->save();

        return $outbox;
    }
}
