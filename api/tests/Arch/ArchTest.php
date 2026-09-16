<?php

declare(strict_types=1);

namespace Tests\Arch;

use FilesystemIterator;
use PHPUnit\Framework\Attributes\Test;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

final class ArchTest extends TestCase
{
    #[Test]
    public function noLedgerModelFileContainsAnUpdateOrDeleteCall(): void
    {
        $ledgerFiles = glob(app_path('Models/*Ledger*.php')) ?: [];

        $this->assertNotEmpty($ledgerFiles);

        foreach ($ledgerFiles as $file) {
            $contents = file_get_contents($file);

            $this->assertStringNotContainsString('->update(', $contents);
            $this->assertStringNotContainsString('->delete(', $contents);
        }
    }

    #[Test]
    public function noEloquentObserverOrListenerWritesAMoneyTypedAttribute(): void
    {
        $hookDirs = array_filter([app_path('Observers'), app_path('Listeners')], 'is_dir');

        if ($hookDirs === []) {
            // Vacuous today: no Observers/Listeners directories exist yet.
            $this->assertTrue(true);

            return;
        }

        foreach ($hookDirs as $dir) {
            foreach (glob($dir.'/*.php') ?: [] as $file) {
                $this->assertDoesNotMatchRegularExpression(
                    '/->(?:price|amount|cost|payout)[A-Za-z_]*\s*=/',
                    file_get_contents($file),
                );
            }
        }
    }

    #[Test]
    public function integrationsIsOnlyUsedWithinItsOwnNamespaceActionsOrServiceProviders(): void
    {
        $this->assertNamespaceOnlyUsedIn('App\Integrations', ['App\Actions', 'App\Providers']);
    }

    #[Test]
    public function dbTransactionIsNeverCalledOutsideAppActions(): void
    {
        $forbiddenDirs = array_filter([
            app_path('Http/Controllers'),
            app_path('Models'),
            app_path('Observers'),
            app_path('Listeners'),
        ], 'is_dir');

        foreach ($forbiddenDirs as $dir) {
            foreach ($this->phpFilesUnder($dir) as $file) {
                $this->assertStringNotContainsString('DB::transaction(', file_get_contents($file));
            }
        }
    }

    #[Test]
    public function noActionFileCallsDbTransactionMoreThanOnce(): void
    {
        foreach ($this->phpFilesUnder(app_path('Actions')) as $file) {
            $occurrences = substr_count(file_get_contents($file), 'DB::transaction(');

            $this->assertLessThanOrEqual(1, $occurrences);
        }
    }

    #[Test]
    public function externallyTriggeredAttributionActionsThatWriteAlsoCatchUniqueConstraintViolationException(): void
    {
        $files = glob(app_path('Actions/Attribution/*.php')) ?: [];

        foreach ($files as $file) {
            $contents = file_get_contents($file);

            if (str_contains($contents, '::create(')) {
                $this->assertStringContainsString('UniqueConstraintViolationException', $contents);
            }
        }
    }

    #[Test]
    public function concretePersonalizationStrategiesAreOnlyConstructedWithinPersonalizationOrProviders(): void
    {
        $this->assertNamespaceOnlyUsedIn('App\Personalization\Strategies', ['App\Personalization', 'App\Providers']);
    }

    #[Test]
    public function concreteCommissionStrategiesAreOnlyConstructedWithinCommissionsOrProviders(): void
    {
        $this->assertNamespaceOnlyUsedIn('App\Commissions\Strategies', ['App\Commissions', 'App\Providers']);
    }

    #[Test]
    public function noBareMatchExpressionDispatchesOnAStrategyOutsideItsRegistry(): void
    {
        $exemptDirs = array_map(app_path(...), ['Commissions', 'Personalization', 'Providers']);

        foreach ($this->phpFilesUnder(app_path()) as $file) {
            foreach ($exemptDirs as $dir) {
                if (str_starts_with(str_replace('\\', '/', $file), str_replace('\\', '/', $dir).'/')) {
                    continue 2;
                }
            }

            $this->assertDoesNotMatchRegularExpression('/\bmatch\s*\(/', file_get_contents($file));
        }
    }

    /**
     * @param  list<string>  $allowedNamespaces
     */
    private function assertNamespaceOnlyUsedIn(string $namespace, array $allowedNamespaces): void
    {
        $exemptNamespaces = [$namespace, ...$allowedNamespaces];

        foreach ($this->phpFilesUnder(app_path()) as $file) {
            foreach ($exemptNamespaces as $exempt) {
                if ($this->fileIsUnderNamespaceDir($file, $exempt)) {
                    continue 2;
                }
            }

            $this->assertStringNotContainsString(
                $namespace.'\\',
                file_get_contents($file),
                "{$file} references {$namespace}, which may only be used in: ".implode(', ', $exemptNamespaces),
            );
        }
    }

    private function fileIsUnderNamespaceDir(string $file, string $namespace): bool
    {
        $dir = app_path(str_replace('\\', '/', substr($namespace, strlen('App\\'))));

        return str_starts_with(
            str_replace('\\', '/', $file),
            str_replace('\\', '/', $dir).'/',
        );
    }

    /**
     * @return list<string>
     */
    private function phpFilesUnder(string $dir): array
    {
        if (! is_dir($dir)) {
            return [];
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        );

        $files = [];

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->getExtension() === 'php') {
                $files[] = $fileInfo->getPathname();
            }
        }

        return $files;
    }
}
