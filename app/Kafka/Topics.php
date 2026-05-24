<?php

declare(strict_types=1);

namespace App\Kafka;

use App\Contracts\DomainEvent;
use App\Events\MoneyDeposited;
use App\Events\MoneyTransferInitiated;
use App\Events\MoneyTransferred;

final class Topics
{
    const MONEY_DEPOSITED = 'bank.money.deposited';

    const MONEY_TRANSFER_INITIATED = 'bank.money.transfer.initiated';

    const MONEY_TRANSFERRED = 'bank.money.transferred';

    const MONEY_DEPOSITED_DLQ = 'bank.money.deposited.dlq';

    const MONEY_TRANSFER_INITIATED_DLQ = 'bank.money.transfer.initiated.dlq';

    const MONEY_TRANSFERRED_DLQ = 'bank.money.transferred.dlq';

    const GROUP_DEPOSITS = 'bank-deposit-consumer';

    const GROUP_TRANSFERS = 'bank-transfer-consumer';

    const GROUP_TRANSFERRED = 'bank-transferred-consumer';

    public static function forEvent(DomainEvent $event): string
    {
        return match (true) {
            $event instanceof MoneyDeposited => self::MONEY_DEPOSITED,
            $event instanceof MoneyTransferInitiated => self::MONEY_TRANSFER_INITIATED,
            $event instanceof MoneyTransferred => self::MONEY_TRANSFERRED,
            default => throw new \InvalidArgumentException('No topic mapped for: '.$event::class),
        };
    }
}
