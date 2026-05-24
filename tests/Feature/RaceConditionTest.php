<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use PDO;
use Tests\TestCase;

/**
 * Race condition (lost update) при конкурентных пополнениях одного счёта.
 *
 * БЕЗ блокировки:
 *   Процесс 1: читает баланс 1000 → вычисляет 1500
 *   Процесс 2: читает баланс 1000 → вычисляет 1500   ← оба видят одно и то же
 *   Процесс 1: записывает 1500
 *   Процесс 2: записывает 1500   ← перезаписывает результат процесса 1!
 *   Итог: 1500 вместо 2000 — одно пополнение потеряно.
 *
 * С SELECT FOR UPDATE:
 *   Процесс 1: читает+блокирует баланс 1000 → пишет 1500 → коммит
 *   Процесс 2: ждёт снятия блокировки → читает 1500 → пишет 2000 → коммит
 *   Итог: 2000 — всё корректно.
 */
class RaceConditionTest extends TestCase
{
    use DatabaseMigrations;

    private bool $isChildProcess = false;

    protected function tearDown(): void
    {
        if ($this->isChildProcess) {
            return;
        }

        parent::tearDown();
    }

    public function test_concurrent_deposits_without_locking_cause_lost_update(): void
    {
        if (! extension_loaded('pcntl')) {
            $this->markTestSkipped('pcntl extension required');
        }

        $account = Account::factory()->withBalance('1000.00')->create();
        $accountId = $account->id;

        // Файлы синхронизации: оба процесса сначала читают, потом оба пишут
        $parentReadFile = sys_get_temp_dir()."/rc_parent_read_{$accountId}";
        $childReadFile = sys_get_temp_dir()."/rc_child_read_{$accountId}";

        DB::disconnect();

        $pid = pcntl_fork();

        if ($pid === -1) {
            $this->fail('pcntl_fork failed');
        }

        if ($pid === 0) {
            // === ДОЧЕРНИЙ: читает баланс, ждёт родителя, пишет своё значение ===
            $this->isChildProcess = true;

            $pdo = $this->newPdo();

            // Читаем баланс БЕЗ блокировки
            $balance = $pdo->query("SELECT balance FROM accounts WHERE id = $accountId")->fetchColumn();
            $newBalance = bcadd((string) $balance, '500.00', 2);

            // Сигнализируем родителю что прочитали
            file_put_contents($childReadFile, $newBalance);

            // Ждём пока родитель тоже прочитает (оба видят одно и то же значение)
            $this->waitForFile($parentReadFile);

            // Оба процесса теперь пишут своё вычисленное значение
            usleep(10000); // чуть позже родителя чтобы гарантированно перезаписать
            $pdo->exec("UPDATE accounts SET balance = '$newBalance' WHERE id = $accountId");

            exit(0);
        }

        // === РОДИТЕЛЬСКИЙ: читает баланс, ждёт ребёнка, пишет своё значение ===
        $pdo = $this->newPdo();

        $balance = $pdo->query("SELECT balance FROM accounts WHERE id = $accountId")->fetchColumn();
        $newBalance = bcadd((string) $balance, '500.00', 2);

        file_put_contents($parentReadFile, $newBalance);

        $this->waitForFile($childReadFile);

        $pdo->exec("UPDATE accounts SET balance = '$newBalance' WHERE id = $accountId");

        pcntl_waitpid($pid, $status);

        @unlink($parentReadFile);
        @unlink($childReadFile);

        DB::reconnect();

        $finalBalance = Account::find($accountId)->balance->amount;

        // Оба пополнили на 500 → ожидаем 2000, но из-за race condition получаем 1500
        $this->assertNotEquals(
            '2000.00',
            $finalBalance,
            'Race condition воспроизведена: одно пополнение потеряно'
        );
        $this->assertEquals('1500.00', $finalBalance, 'Только одно пополнение применилось');
    }

    public function test_concurrent_deposits_with_select_for_update_are_safe(): void
    {
        if (! extension_loaded('pcntl')) {
            $this->markTestSkipped('pcntl extension required');
        }

        $user = User::factory()->create();
        $account = Account::factory()->withBalance('1000.00')->create(['user_id' => $user->id]);
        $accountId = $account->id;

        DB::disconnect();

        $pid = pcntl_fork();

        if ($pid === -1) {
            $this->fail('pcntl_fork failed');
        }

        if ($pid === 0) {
            // === ДОЧЕРНИЙ: пополняет через SELECT FOR UPDATE ===
            $this->isChildProcess = true;

            $pdo = $this->newPdo();
            $pdo->beginTransaction();

            // SELECT FOR UPDATE — ждёт пока родитель отпустит блокировку
            $balance = $pdo->query(
                "SELECT balance FROM accounts WHERE id = $accountId FOR UPDATE"
            )->fetchColumn();

            $newBalance = bcadd((string) $balance, '500.00', 2);
            $pdo->exec("UPDATE accounts SET balance = '$newBalance' WHERE id = $accountId");
            $pdo->commit();

            exit(0);
        }

        // === РОДИТЕЛЬСКИЙ: пополняет через SELECT FOR UPDATE ===
        $pdo = $this->newPdo();
        $pdo->beginTransaction();

        $balance = $pdo->query(
            "SELECT balance FROM accounts WHERE id = $accountId FOR UPDATE"
        )->fetchColumn();

        $newBalance = bcadd((string) $balance, '500.00', 2);
        $pdo->exec("UPDATE accounts SET balance = '$newBalance' WHERE id = $accountId");
        $pdo->commit();

        pcntl_waitpid($pid, $status);

        DB::reconnect();

        $finalBalance = Account::find($accountId)->balance->amount;

        // SELECT FOR UPDATE гарантирует что оба пополнения применились
        $this->assertEquals('2000.00', $finalBalance, 'Оба пополнения корректно применились');
    }

    private function newPdo(): PDO
    {
        $c = config('database.connections.pgsql');
        $dsn = "pgsql:host={$c['host']};port={$c['port']};dbname={$c['database']}";
        $pdo = new PDO($dsn, $c['username'], $c['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }

    private function waitForFile(string $path, int $timeoutMs = 3000): void
    {
        $deadline = microtime(true) + $timeoutMs / 1000;

        while (! file_exists($path)) {
            if (microtime(true) > $deadline) {
                exit(2);
            }
            usleep(5000);
        }
    }
}
