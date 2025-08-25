<?php

declare(strict_types=1);

/**
 * This file is part of Daycry Schemas.
 *
 * (c) Daycry <daycry9@proton.me>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Daycry\Schemas\Plugins\Examples;

use Daycry\Schemas\Plugins\BasePlugin;
use Daycry\Schemas\Events\EventInterface;
use Daycry\Schemas\Events\SchemaEvents;

/**
 * Example plugin that logs schema operations
 */
class LoggerPlugin extends BasePlugin
{
    protected string $name = 'Schema Logger';
    protected string $version = '1.0.0';
    protected string $description = 'Logs all schema operations for debugging and monitoring';
    protected array|string $author = 'Daycry Schemas Team';

    public function getSubscribedEvents(): array
    {
        $baseEvents = [
            SchemaEvents::SCHEMA_BEFORE_DRAFT,
            SchemaEvents::SCHEMA_AFTER_DRAFT,
            SchemaEvents::SCHEMA_BEFORE_ARCHIVE,
            SchemaEvents::SCHEMA_AFTER_ARCHIVE,
            SchemaEvents::SCHEMA_BEFORE_READ,
            SchemaEvents::SCHEMA_AFTER_READ,
            SchemaEvents::TABLE_DISCOVERED,
            SchemaEvents::RELATIONSHIP_DETECTED,
            SchemaEvents::PERFORMANCE_ISSUE_DETECTED,
            SchemaEvents::ERROR_OCCURRED,
        ];

        // Apply filters
        if (!empty($this->getConfig('include_events'))) {
            $baseEvents = array_intersect($baseEvents, $this->getConfig('include_events'));
        }

        if (!empty($this->getConfig('exclude_events'))) {
            $baseEvents = array_diff($baseEvents, $this->getConfig('exclude_events'));
        }

        return array_values($baseEvents);
    }

    public function getConfigSchema(): array
    {
        return [
            'enabled' => [
                'type' => 'boolean',
                'required' => true,
                'default' => true,
            ],
            'log_level' => [
                'type' => 'string',
                'required' => false,
                'default' => 'info',
                'options' => ['debug', 'info', 'warning', 'error'],
            ],
            'log_file' => [
                'type' => 'string',
                'required' => false,
                'default' => WRITEPATH . 'logs/schemas.log',
            ],
            'include_data' => [
                'type' => 'boolean',
                'required' => false,
                'default' => false,
            ],
        ];
    }

    protected function doInitialize(): void
    {
        $logFile = $this->getConfig('log_file');
        
        if ($logFile && !is_dir(dirname($logFile))) {
            mkdir(dirname($logFile), 0755, true);
        }
    }

    protected function doHandleEvent(EventInterface $event): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $eventName = $event->getName();
        $logLevel = $this->getLogLevel($eventName);
        
        if (!$this->shouldLog($logLevel)) {
            return;
        }

        $message = $this->formatMessage($event);
        $this->writeLog($logLevel, $message, $event);
    }

    /**
     * Determine log level based on event type
     */
    protected function getLogLevel(string $eventName): string
    {
        if (str_contains($eventName, 'error')) {
            return 'error';
        }
        
        if (str_contains($eventName, 'warning') || str_contains($eventName, 'issue')) {
            return 'warning';
        }
        
        if (str_contains($eventName, 'before') || str_contains($eventName, 'after')) {
            return 'info';
        }
        
        return 'debug';
    }

    /**
     * Check if we should log at this level
     */
    protected function shouldLog(string $level): bool
    {
        $configLevel = $this->getConfig('log_level', 'info');
        $levels = ['debug' => 0, 'info' => 1, 'warning' => 2, 'error' => 3];
        
        return $levels[$level] >= $levels[$configLevel];
    }

    /**
     * Format the log message
     */
    protected function formatMessage(EventInterface $event): string
    {
        $eventName = $event->getName();
        $target = $event->getTarget();
        $data = $event->getData();
        
        $message = "[{$eventName}]";
        
        if ($target) {
            $message .= " Target: " . get_class($target);
        }
        
        if ($this->getConfig('include_data', false) && !empty($data)) {
            $filteredData = $this->filterSensitiveData($data);
            $message .= " Data: " . json_encode($filteredData, JSON_UNESCAPED_SLASHES);
        }
        
        return $message;
    }

    /**
     * Filter sensitive data from logs
     */
    protected function filterSensitiveData(array $data): array
    {
        $sensitive = ['password', 'token', 'secret', 'key', 'auth'];
        
        foreach ($data as $key => $value) {
            foreach ($sensitive as $pattern) {
                if (str_contains(strtolower($key), $pattern)) {
                    $data[$key] = '[FILTERED]';
                    break;
                }
            }
            
            if (is_array($value)) {
                $data[$key] = $this->filterSensitiveData($value);
            }
        }
        
        return $data;
    }

    /**
     * Write log entry
     */
    protected function writeLog(string $level, string $message, EventInterface $event): void
    {
        $timestamp = date('Y-m-d H:i:s', (int) $event->getTimestamp());
        $logEntry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        
        $logFile = $this->getConfig('log_file');
        
        if ($logFile) {
            file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        }
        
        // Also log to CodeIgniter logger if available
        if (function_exists('log_message')) {
            log_message($level, $message);
        }
    }

    public function getDependencies(): array
    {
        return []; // No dependencies
    }

    protected function doDisable(): void
    {
        // Cleanup if needed
        $logFile = $this->getConfig('log_file');
        
        if ($logFile && file_exists($logFile)) {
            $this->writeLog('info', 'Logger plugin disabled', new \Daycry\Schemas\Events\BaseEvent('plugin.disabled'));
        }
    }
}
