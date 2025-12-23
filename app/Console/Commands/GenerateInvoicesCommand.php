<?php

namespace App\Console\Commands;

use App\Services\V1\Invoice\AutomatedInvoiceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateInvoicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate invoices for active contracts based on payment frequency';

    /**
     * Execute the console command.
     */
    public function handle(AutomatedInvoiceService $automatedInvoiceService): int
    {
        $logger = Log::channel('invoices');
        $startTime = microtime(true);
        
        $this->info('=== Invoice Generation Command Started ===');
        $this->info('Timestamp: ' . now()->toDateTimeString());
        $this->newLine();
        
        $logger->info('Command executed', [
            'command' => 'invoices:generate',
            'timestamp' => now()->toDateTimeString(),
        ]);
        
        try {
            $this->info('Starting invoice generation process...');
            $this->newLine();
            
            $results = $automatedInvoiceService->generateInvoicesForDueContracts();
            
            $generated = $results['total_generated'];
            $skipped = count($results['skipped']);
            $executionTime = round(microtime(true) - $startTime, 2);
            
            $this->newLine();
            $this->info('=== Results ===');
            $this->info("Total invoices generated: {$generated}");
            $this->info("Execution time: {$executionTime} seconds");
            $this->newLine();
            
            if ($generated > 0) {
                $this->info('Generated invoices:');
                foreach ($results['generated'] as $gen) {
                    $this->line("  ✓ Invoice ID: {$gen['invoice_id']} | Contract ID: {$gen['contract_id']} | Ownership ID: {$gen['ownership_id']}");
                }
                $this->newLine();
            }
            
            if ($skipped > 0) {
                $this->warn("Skipped contracts: {$skipped}");
                foreach ($results['skipped'] as $skip) {
                    $this->warn("  ✗ Contract {$skip['contract_id']} (Ownership {$skip['ownership_id']}): {$skip['error']}");
                }
                $this->newLine();
            }
            
            if ($generated > 0) {
                $this->info('✓ Invoice generation completed successfully.');
            } else {
                $this->info('ℹ No invoices generated (no contracts due or auto-generation disabled).');
            }
            
            $logger->info('Command completed successfully', [
                'total_generated' => $generated,
                'total_skipped' => $skipped,
                'execution_time_seconds' => $executionTime,
            ]);
            
            $this->newLine();
            $this->info('=== Command Finished ===');
            $this->info("Log file: storage/logs/invoices-" . now()->format('Y-m-d') . ".log");
            
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('An error occurred during invoice generation:');
            $this->error($e->getMessage());
            $this->newLine();
            
            $logger->error('Command failed with exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return Command::FAILURE;
        }
    }
}
