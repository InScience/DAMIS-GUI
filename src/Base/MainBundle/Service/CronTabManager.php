<?php

namespace Base\MainBundle\Service;

use Base\MainBundle\Repository\CronJobRepository;
use Psr\Log\LoggerInterface;

/**
 * Service to manage synchronization between database cron jobs and system crontab
 */
class CronTabManager
{
    private const MARKER_BEGIN = '# BEGIN DAMIS-GUI MANAGED JOBS - DO NOT EDIT MANUALLY';
    private const MARKER_END = '# END DAMIS-GUI MANAGED JOBS';
    
    private CronJobRepository $cronJobRepository;
    private LoggerInterface $logger;
    private string $projectDir;
    private ?string $lastError = null;

    public function __construct(
        CronJobRepository $cronJobRepository,
        LoggerInterface $logger,
        string $projectDir
    ) {
        $this->cronJobRepository = $cronJobRepository;
        $this->logger = $logger;
        $this->projectDir = $projectDir;
    }

    /**
     * Synchronize database cron jobs to system crontab
     * 
     * @return bool True if sync was successful, false otherwise
     */
    public function syncToSystemCrontab(): bool
    {
        try {
            $this->logger->info('Starting crontab synchronization');
            
            // Read current crontab
            $currentCrontab = $this->readCurrentCrontab();
            
            // Parse and extract user's manual jobs (outside our markers)
            $userJobs = $this->extractUserJobs($currentCrontab);
            
            // Generate new crontab content
            $newCrontab = $this->generateCrontabContent($userJobs);
            
            // Backup current crontab (non-fatal if it fails)
            try {
                $this->backupCrontab($currentCrontab);
            } catch (\Exception $e) {
                $this->logger->warning('Failed to backup crontab, continuing anyway', [
                    'error' => $e->getMessage()
                ]);
            }
            
            // Write new crontab to system
            $success = $this->writeCrontab($newCrontab);
            
            if ($success) {
                $this->logger->info('Crontab synchronization completed successfully');
            } else {
                $this->logger->error('Crontab synchronization failed', ['error' => $this->lastError]);
            }
            
            return $success;
            
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            $this->logger->error('Exception during crontab sync', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Get the last error message
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Read current system crontab
     */
    private function readCurrentCrontab(): string
    {
        $output = [];
        $returnCode = 0;
        
        exec('crontab -l 2>/dev/null', $output, $returnCode);
        
        // Return code 1 usually means "no crontab" which is fine
        if ($returnCode !== 0 && $returnCode !== 1) {
            throw new \RuntimeException('Failed to read crontab. Return code: ' . $returnCode);
        }
        
        return implode("\n", $output);
    }

    /**
     * Extract user's manual cron jobs (everything outside DAMIS markers)
     */
    private function extractUserJobs(string $crontab): array
    {
        if (empty($crontab)) {
            return [];
        }
        
        $lines = explode("\n", $crontab);
        $userJobs = [];
        $insideDamisSection = false;
        
        foreach ($lines as $line) {
            if (strpos($line, self::MARKER_BEGIN) !== false) {
                $insideDamisSection = true;
                continue;
            }
            
            if (strpos($line, self::MARKER_END) !== false) {
                $insideDamisSection = false;
                continue;
            }
            
            // Skip our own added headers
            if (trim($line) === '# User-defined cron jobs') {
                continue;
            }
            
            // Only keep lines outside DAMIS section
            if (!$insideDamisSection && trim($line) !== '') {
                $userJobs[] = $line;
            }
        }
        
        return $userJobs;
    }

    /**
     * Generate complete crontab content with user jobs + DAMIS jobs
     */
    private function generateCrontabContent(array $userJobs): string
    {
        $content = [];
        
        // Add user's manual jobs first
        if (!empty($userJobs)) {
            $content[] = '# User-defined cron jobs';
            $content = array_merge($content, $userJobs);
            $content[] = ''; // Empty line for separation
        }
        
        // Add DAMIS managed section
        $content[] = self::MARKER_BEGIN;
        $content[] = '# This section is automatically managed by DAMIS-GUI';
        $content[] = '# Any manual edits here will be overwritten';
        $content[] = '# Last sync: ' . date('Y-m-d H:i:s');
        $content[] = '';
        
        // Get all enabled jobs from database
        $cronJobs = $this->cronJobRepository->findAllEnabled();
        
        if (empty($cronJobs)) {
            $content[] = '# No active cron jobs configured';
        } else {
            foreach ($cronJobs as $cronJob) {
                // Add job description as comment
                $content[] = '# Job ID: ' . $cronJob->getId() . ' - ' . $cronJob->getName();
                if ($cronJob->getDescription()) {
                    $content[] = '# ' . $cronJob->getDescription();
                }
                
                // Add the actual cron line
                $content[] = $cronJob->getFullCronExpression();
                $content[] = ''; // Empty line between jobs
            }
        }
        
        $content[] = self::MARKER_END;
        
        return implode("\n", $content) . "\n";
    }

    /**
     * Write crontab content to system
     */
    private function writeCrontab(string $content): bool
    {
        // Write to temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'crontab_');
        
        if ($tempFile === false) {
            $this->lastError = 'Failed to create temporary file';
            return false;
        }
        
        try {
            // Write content to temp file
            if (file_put_contents($tempFile, $content) === false) {
                $this->lastError = 'Failed to write to temporary file';
                return false;
            }
            
            // Install the new crontab
            $output = [];
            $returnCode = 0;
            exec('crontab ' . escapeshellarg($tempFile) . ' 2>&1', $output, $returnCode);
            
            if ($returnCode !== 0) {
                $this->lastError = 'Failed to install crontab: ' . implode("\n", $output);
                return false;
            }
            
            return true;
            
        } finally {
            // Clean up temp file
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    /**
     * Backup current crontab to a file
     */
    private function backupCrontab(string $content): void
    {
        if (empty($content)) {
            return;
        }
        
        $backupDir = $this->projectDir . '/var/crontab_backups';
        
        // Create backup directory if it doesn't exist with write permissions
        if (!is_dir($backupDir)) {
            if (!mkdir($backupDir, 0777, true)) {
                throw new \RuntimeException('Failed to create backup directory: ' . $backupDir);
            }
            chmod($backupDir, 0777); // Ensure permissions are set
        }
        
        $backupFile = $backupDir . '/crontab_backup_' . date('Y-m-d_His') . '.txt';
        
        if (file_put_contents($backupFile, $content) === false) {
            throw new \RuntimeException('Failed to write backup file: ' . $backupFile);
        }
        
        // Make backup file readable by all
        @chmod($backupFile, 0666);
        
        $this->logger->info('Crontab backed up', ['file' => $backupFile]);
        
        // Keep only last 10 backups
        $this->cleanOldBackups($backupDir, 10);
    }

    /**
     * Remove old backup files, keeping only the most recent ones
     */
    private function cleanOldBackups(string $backupDir, int $keepCount): void
    {
        $files = glob($backupDir . '/crontab_backup_*.txt');
        
        if (count($files) <= $keepCount) {
            return;
        }
        
        // Sort by modification time, newest first
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        // Remove old backups
        $filesToDelete = array_slice($files, $keepCount);
        foreach ($filesToDelete as $file) {
            unlink($file);
        }
    }

    /**
     * Validate cron expression format
     */
    public function validateCronExpression(string $expression): bool
    {
        // Basic validation - should have 5 parts (minute hour day month weekday)
        $parts = preg_split('/\s+/', trim($expression));
        
        if (count($parts) < 5) {
            return false;
        }
        
        // Each part should be valid cron syntax (numbers, *, /, -, ,)
        foreach (array_slice($parts, 0, 5) as $part) {
            if (!preg_match('/^[\d*,\-\/]+$/', $part)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get cron user (who will run the jobs)
     */
    public function getCronUser(): string
    {
        $user = get_current_user();
        
        if (function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
            $processUser = posix_getpwuid(posix_geteuid());
            if ($processUser && isset($processUser['name'])) {
                $user = $processUser['name'];
            }
        }
        
        return $user;
    }

    /**
     * Test if we have permission to modify crontab
     */
    public function testCrontabAccess(): bool
    {
        try {
            $this->readCurrentCrontab();
            return true;
        } catch (\Exception $e) {
            $this->lastError = 'No permission to access crontab: ' . $e->getMessage();
            return false;
        }
    }
}

