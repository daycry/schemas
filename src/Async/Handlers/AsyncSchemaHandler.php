<?php

declare(strict_types=1);

namespace Daycry\Schemas\Async\Handlers;

use Daycry\Schemas\Async\AsyncJob;
use Daycry\Schemas\Async\BaseAsyncHandler;
use Daycry\Schemas\Schemas;
use Daycry\Schemas\Reader\ReaderInterface;
use Daycry\Schemas\Archiver\ArchiverInterface;

/**
 * Asynchronous handler for schema operations
 */
class AsyncSchemaHandler extends BaseAsyncHandler
{
    public const OPERATION_READ = 'read';
    public const OPERATION_ARCHIVE = 'archive';
    public const OPERATION_DRAFT = 'draft';
    public const OPERATION_VALIDATE = 'validate';
    public const OPERATION_COMPARE = 'compare';
    public const OPERATION_MERGE = 'merge';

    private Schemas $schemas;
    private ?ReaderInterface $reader = null;
    private ?ArchiverInterface $archiver = null;

    public function __construct(Schemas $schemas, array $config = [])
    {
        parent::__construct($config);
        $this->schemas = $schemas;
    }

    public function setReader(ReaderInterface $reader): void
    {
        $this->reader = $reader;
    }

    public function setArchiver(ArchiverInterface $archiver): void
    {
        $this->archiver = $archiver;
    }

    public function supports(string $operationType): bool
    {
        return in_array($operationType, $this->getSupportedOperations());
    }

    public function getSupportedOperations(): array
    {
        return [
            self::OPERATION_READ,
            self::OPERATION_ARCHIVE,
            self::OPERATION_DRAFT,
            self::OPERATION_VALIDATE,
            self::OPERATION_COMPARE,
            self::OPERATION_MERGE,
        ];
    }

    protected function doProcess(AsyncJob $job): void
    {
        $operationType = $job->getType();
        $data = $job->getData();
        $options = $job->getOptions();

        switch ($operationType) {
            case self::OPERATION_READ:
                $result = $this->processRead($job, $data, $options);
                break;

            case self::OPERATION_ARCHIVE:
                $result = $this->processArchive($job, $data, $options);
                break;

            case self::OPERATION_DRAFT:
                $result = $this->processDraft($job, $data, $options);
                break;

            case self::OPERATION_VALIDATE:
                $result = $this->processValidate($job, $data, $options);
                break;

            case self::OPERATION_COMPARE:
                $result = $this->processCompare($job, $data, $options);
                break;

            case self::OPERATION_MERGE:
                $result = $this->processMerge($job, $data, $options);
                break;

            default:
                throw new \InvalidArgumentException("Unsupported operation type: {$operationType}");
        }

        $job->setResult($result);
    }

    /**
     * Process schema reading operation
     */
    protected function processRead(AsyncJob $job, $data, array $options): array
    {
        if (!$this->reader) {
            throw new \RuntimeException('Reader not configured for async schema operations');
        }

        $job->setProgress(0, 100, 'Starting schema read...');

        $tables = is_array($data) ? $data : [$data];
        $results = [];
        $total = count($tables);

        foreach ($tables as $index => $table) {
            $job->setProgress($index, $total, "Reading schema for table: {$table}");

            try {
                $this->reader->fetch($table);
                $results[$table] = [
                    'success' => true,
                    'message' => 'Table fetched successfully',
                ];
                
                $job->addMetadata("table_{$table}_fetched", true);
                
            } catch (\Throwable $e) {
                $results[$table] = [
                    'error' => $e->getMessage(),
                    'success' => false,
                ];
            }

            // Update progress
            $nextIndex = $index + 1;
            $job->setProgress($nextIndex, $total, "Completed {$nextIndex} of {$total} tables");
        }

        $job->setProgress($total, $total, 'Schema reading completed');
        return $results;
    }

    /**
     * Process schema archiving operation
     */
    protected function processArchive(AsyncJob $job, $data, array $options): array
    {
        if (!$this->archiver) {
            throw new \RuntimeException('Archiver not configured for async schema operations');
        }

        $job->setProgress(0, 100, 'Starting schema archive...');

        $schemas = is_array($data) ? $data : [$data];
        $results = [];
        $total = count($schemas);

        foreach ($schemas as $index => $schema) {
            $database = $schema['database'] ?? "unknown_{$index}";
            $job->setProgress($index, $total, "Archiving schema for database: {$database}");

            try {
                $archiveResult = $this->archiver->archive($schema);
                $results[$database] = [
                    'archive_result' => $archiveResult,
                    'success' => $archiveResult,
                ];
                
                $job->addMetadata("archive_{$database}_success", $archiveResult);
                
            } catch (\Throwable $e) {
                $results[$database] = [
                    'error' => $e->getMessage(),
                    'success' => false,
                ];
            }

            // Update progress
            $nextIndex = $index + 1;
            $job->setProgress($nextIndex, $total, "Completed {$nextIndex} of {$total} archives");
        }

        $job->setProgress($total, $total, 'Schema archiving completed');
        return $results;
    }

    /**
     * Process schema drafting operation
     */
    protected function processDraft(AsyncJob $job, $data, array $options): array
    {
        $job->setProgress(0, 100, 'Starting schema draft...');

        $schemas = is_array($data) ? $data : [$data];
        $results = [];
        $total = count($schemas);

        foreach ($schemas as $index => $schema) {
            $database = $schema['database'] ?? "unknown_{$index}";
            $job->setProgress($index, $total, "Drafting schema for database: {$database}");

            try {
                $draftResult = $this->schemas->draft($options['handlers'] ?? null);
                $results[$database] = [
                    'draft' => $draftResult,
                    'success' => true,
                ];
                
            } catch (\Throwable $e) {
                $results[$database] = [
                    'error' => $e->getMessage(),
                    'success' => false,
                ];
            }

            $nextIndex = $index + 1;
            $job->setProgress($nextIndex, $total, "Completed {$nextIndex} of {$total} drafts");
        }

        $job->setProgress($total, $total, 'Schema drafting completed');
        return $results;
    }

    /**
     * Process schema validation operation
     */
    protected function processValidate(AsyncJob $job, $data, array $options): array
    {
        $job->setProgress(0, 100, 'Starting schema validation...');

        $schemas = is_array($data) ? $data : [$data];
        $results = [];
        $total = count($schemas);

        foreach ($schemas as $index => $schema) {
            $database = $schema['database'] ?? "unknown_{$index}";
            $job->setProgress($index, $total, "Validating schema for database: {$database}");

            try {
                // Simulate schema validation
                $validation = $this->validateSchema($schema, $options);
                $results[$database] = [
                    'validation' => $validation,
                    'success' => true,
                ];
                
                $job->addMetadata("validation_{$database}_errors", count($validation['errors'] ?? []));
                
            } catch (\Throwable $e) {
                $results[$database] = [
                    'error' => $e->getMessage(),
                    'success' => false,
                ];
            }

            $nextIndex = $index + 1;
            $job->setProgress($nextIndex, $total, "Completed {$nextIndex} of {$total} validations");
        }

        $job->setProgress($total, $total, 'Schema validation completed');
        return $results;
    }

    /**
     * Process schema comparison operation
     */
    protected function processCompare(AsyncJob $job, $data, array $options): array
    {
        $job->setProgress(0, 100, 'Starting schema comparison...');

        $sourceSchema = $data['source'] ?? null;
        $targetSchema = $data['target'] ?? null;

        if (!$sourceSchema || !$targetSchema) {
            throw new \InvalidArgumentException('Both source and target schemas are required for comparison');
        }

        $job->setProgress(50, 100, 'Comparing schemas...');

        try {
            $comparison = $this->compareSchemas($sourceSchema, $targetSchema, $options);
            $result = [
                'comparison' => $comparison,
                'success' => true,
            ];
            
            $job->addMetadata('differences_found', count($comparison['differences'] ?? []));
            
        } catch (\Throwable $e) {
            $result = [
                'error' => $e->getMessage(),
                'success' => false,
            ];
        }

        $job->setProgress(100, 100, 'Schema comparison completed');
        return $result;
    }

    /**
     * Process schema merge operation
     */
    protected function processMerge(AsyncJob $job, $data, array $options): array
    {
        $job->setProgress(0, 100, 'Starting schema merge...');

        $schemas = $data['schemas'] ?? [];
        if (count($schemas) < 2) {
            throw new \InvalidArgumentException('At least 2 schemas are required for merging');
        }

        $total = count($schemas);
        $job->setProgress(25, 100, "Merging {$total} schemas...");

        try {
            $mergedSchema = $this->mergeSchemas($schemas, $options);
            $result = [
                'merged_schema' => $mergedSchema,
                'success' => true,
            ];
            
            $job->addMetadata('schemas_merged', $total);
            $job->addMetadata('merged_tables', count($mergedSchema['tables'] ?? []));
            
        } catch (\Throwable $e) {
            $result = [
                'error' => $e->getMessage(),
                'success' => false,
            ];
        }

        $job->setProgress(100, 100, 'Schema merge completed');
        return $result;
    }

    /**
     * Validate a schema
     */
    protected function validateSchema(array $schema, array $options): array
    {
        $errors = [];
        $warnings = [];

        // Basic validation logic
        if (!isset($schema['database'])) {
            $errors[] = 'Database name is required';
        }

        if (!isset($schema['tables']) || !is_array($schema['tables'])) {
            $errors[] = 'Tables array is required';
        } else {
            foreach ($schema['tables'] as $tableName => $table) {
                if (!isset($table['columns']) || empty($table['columns'])) {
                    $warnings[] = "Table '{$tableName}' has no columns defined";
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Compare two schemas
     */
    protected function compareSchemas(array $source, array $target, array $options): array
    {
        $differences = [];

        // Basic comparison logic
        $sourceTables = $source['tables'] ?? [];
        $targetTables = $target['tables'] ?? [];

        // Find missing tables
        foreach ($sourceTables as $tableName => $table) {
            if (!isset($targetTables[$tableName])) {
                $differences[] = [
                    'type' => 'missing_table',
                    'table' => $tableName,
                    'message' => "Table '{$tableName}' exists in source but not in target",
                ];
            }
        }

        // Find extra tables
        foreach ($targetTables as $tableName => $table) {
            if (!isset($sourceTables[$tableName])) {
                $differences[] = [
                    'type' => 'extra_table',
                    'table' => $tableName,
                    'message' => "Table '{$tableName}' exists in target but not in source",
                ];
            }
        }

        return [
            'differences' => $differences,
            'identical' => empty($differences),
        ];
    }

    /**
     * Merge multiple schemas
     */
    protected function mergeSchemas(array $schemas, array $options): array
    {
        $mergedSchema = [
            'database' => $options['target_database'] ?? 'merged_database',
            'tables' => [],
        ];

        foreach ($schemas as $schema) {
            $tables = $schema['tables'] ?? [];
            foreach ($tables as $tableName => $table) {
                if (!isset($mergedSchema['tables'][$tableName])) {
                    $mergedSchema['tables'][$tableName] = $table;
                } else {
                    // Merge table definitions (basic implementation)
                    $mergedSchema['tables'][$tableName] = array_merge(
                        $mergedSchema['tables'][$tableName],
                        $table
                    );
                }
            }
        }

        return $mergedSchema;
    }
}
