<?php

namespace Tests\Feature;

use App\Models\Account;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use PDO;
use PDOException;
use Tests\TestCase;

/**
 * Воспроизводит deadlock при встречных переводах:
 *
 *   Процесс A: блокирует счёт 1 → пытается заблокировать счёт 2
 *   Процесс B: блокирует счёт 2 → пытается заблокировать счёт 1
 *
 *   PostgreSQL обнаруживает цикл ожидания и убивает одну из транзакций (40P01).
 *
 * Исправление: всегда блокировать счета в порядке возрастания ID.
 * Тогда оба процесса конкурируют за один и тот же первый замок — второй просто ждёт.
 */
class TransferDeadlockTest extends TestCase
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

    public function test_opposite_concurrent_transfers_cause_deadlock(): void
    {
        if (! extension_loaded('pcntl')) {
            $this->markTestSkipped('pcntl extension required');
        }

        $accountA = Account::factory()->withBalance('1000.00')->create();
        $accountB = Account::factory()->withBalance('1000.00')->create();

        $aId = $accountA->id;
        $bId = $accountB->id;

        // Файлы для синхронизации между процессами
        $aLockedFile = sys_get_temp_dir()."/deadlock_a_{$aId}";
        $bLockedFile = sys_get_temp_dir()."/deadlock_b_{$bId}";

        DB::disconnect();

        $pid = pcntl_fork();

        if ($pid === -1) {
            $this->fail('pcntl_fork failed');
        }

        if ($pid === 0) {
            // === ДОЧЕРНИЙ ПРОЦЕСС: блокирует B, потом пытается заблокировать A ===
            $this->isChildProcess = true;

            $pdo = $this->newPdo();
            $pdo->exec("SET deadlock_timeout = '200ms'");
            $pdo->beginTransaction();

            // Ждём пока родитель заблокирует A
            $this->waitForFile($aLockedFile);

            // Блокируем B и сигнализируем родителю
            $pdo->query("SELECT id FROM accounts WHERE id = $bId FOR UPDATE");
            file_put_contents($bLockedFile, '1');

            // Пытаемся заблокировать A — родитель его держит → DEADLOCK
            try {
                $pdo->query("SELECT id FROM accounts WHERE id = $aId FOR UPDATE");
                $pdo->rollBack();
                exit(0); // Повезло — не жертва
            } catch (PDOException) {
                $pdo->rollBack();
                exit(1); // Жертва deadlock
            }
        }

        // === РОДИТЕЛЬСКИЙ ПРОЦЕСС: блокирует A, потом пытается заблокировать B ===
        $pdo = $this->newPdo();
        $pdo->exec("SET deadlock_timeout = '200ms'");
        $pdo->beginTransaction();

        // Блокируем A и сигнализируем дочернему
        $pdo->query("SELECT id FROM accounts WHERE id = $aId FOR UPDATE");
        file_put_contents($aLockedFile, '1');

        // Ждём пока дочерний заблокирует B
        $this->waitForFile($bLockedFile);

        // Пытаемся заблокировать B — дочерний его держит → DEADLOCK
        $parentIsVictim = false;
        try {
            $pdo->query("SELECT id FROM accounts WHERE id = $bId FOR UPDATE");
            $pdo->rollBack();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $parentIsVictim = str_contains($e->getMessage(), 'deadlock');
        }

        pcntl_waitpid($pid, $childExitStatus);
        $childIsVictim = pcntl_wexitstatus($childExitStatus) === 1;

        @unlink($aLockedFile);
        @unlink($bLockedFile);

        DB::reconnect();

        $this->assertTrue(
            $parentIsVictim || $childIsVictim,
            'PostgreSQL должен обнаружить deadlock (SQLSTATE 40P01) и прервать одну из транзакций'
        );
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
