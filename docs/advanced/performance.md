# Performance Analysis

The Performance Analysis system helps identify bottlenecks, optimization opportunities, and performance issues in your database schema. It provides actionable recommendations to improve query performance and overall database efficiency.

## Overview

The `PerformanceAnalyzer` class analyzes your schema and provides:

- **Performance Scoring**: Numerical scores from 0-100 for optimization priority
- **Index Analysis**: Evaluates index efficiency and identifies missing indexes
- **Foreign Key Performance**: Analyzes foreign key impact on performance
- **Query Pattern Analysis**: Identifies common query performance issues
- **Optimization Recommendations**: Specific, actionable suggestions for improvements
- **Bottleneck Detection**: Pinpoints areas causing performance degradation

## Basic Usage

```php
use Daycry\Schemas\PerformanceAnalyzer;
use Daycry\Schemas\Schemas;

// Load your schema
$schemas = new Schemas();
$schema = $schemas->get();

// Create analyzer
$analyzer = new PerformanceAnalyzer();

// Analyze performance
$analysis = $analyzer->analyzeSchema($schema);

// Display overall score
echo "Performance Score: {$analysis['score']}/100\n";

// Show recommendations
echo "\nRecommendations:\n";
foreach ($analysis['recommendations'] as $rec) {
    echo "  Priority: {$rec['priority']}\n";
    echo "  Table: {$rec['table']}\n";
    echo "  Message: {$rec['message']}\n";
    echo "  Impact: {$rec['impact']}\n\n";
}

// Table-specific scores
echo "\nTable Scores:\n";
foreach ($analysis['table_scores'] as $tableName => $score) {
    echo "  {$tableName}: {$score}/100\n";
}
```

## Configuration

Configure the analyzer for your specific needs:

```php
$analyzer = new PerformanceAnalyzer([
    'scoring' => [
        'weights' => [
            'indexes' => 0.4,           // 40% weight for index analysis
            'foreign_keys' => 0.3,      // 30% weight for FK analysis
            'data_types' => 0.2,        // 20% weight for data type efficiency
            'table_structure' => 0.1,   // 10% weight for table structure
        ],
        'thresholds' => [
            'excellent' => 90,          // Score >= 90 is excellent
            'good' => 70,               // Score >= 70 is good
            'fair' => 50,               // Score >= 50 is fair
            'poor' => 30,               // Score < 30 is poor
        ],
    ],
    'analysis' => [
        'max_recommendations' => 20,   // Maximum recommendations to return
        'min_priority' => 'medium',    // Minimum priority level to include
        'include_suggestions' => true,  // Include optimization suggestions
        'detailed_analysis' => true,   // Include detailed analysis data
    ],
    'database_specific' => [
        'mysql' => [
            'analyze_query_cache' => true,
            'check_innodb_settings' => true,
            'evaluate_partitioning' => false,
        ],
        'postgresql' => [
            'analyze_statistics' => true,
            'check_vacuum_settings' => true,
            'evaluate_extensions' => true,
        ],
    ],
]);
```

## Analysis Components

### 1. Index Analysis

Evaluates index efficiency and identifies optimization opportunities:

```php
// Get detailed index analysis
$indexAnalysis = $analyzer->analyzeIndexes($schema);

foreach ($indexAnalysis as $tableName => $tableAnalysis) {
    echo "Table: {$tableName}\n";
    
    // Missing indexes
    if (!empty($tableAnalysis['missing_indexes'])) {
        echo "  Missing Indexes:\n";
        foreach ($tableAnalysis['missing_indexes'] as $missing) {
            echo "    - {$missing['fields']}: {$missing['reason']}\n";
        }
    }
    
    // Inefficient indexes
    if (!empty($tableAnalysis['inefficient_indexes'])) {
        echo "  Inefficient Indexes:\n";
        foreach ($tableAnalysis['inefficient_indexes'] as $inefficient) {
            echo "    - {$inefficient['name']}: {$inefficient['issue']}\n";
        }
    }
    
    // Unused indexes
    if (!empty($tableAnalysis['unused_indexes'])) {
        echo "  Potentially Unused Indexes:\n";
        foreach ($tableAnalysis['unused_indexes'] as $unused) {
            echo "    - {$unused['name']}: {$unused['reason']}\n";
        }
    }
}
```

**Common Index Issues Detected:**
- Missing indexes on foreign key columns
- Missing indexes on frequently queried columns
- Redundant or duplicate indexes
- Overly broad composite indexes
- Indexes with poor selectivity

### 2. Foreign Key Performance Analysis

Analyzes the performance impact of foreign key relationships:

```php
$foreignKeyAnalysis = $analyzer->analyzeForeignKeys($schema);

foreach ($foreignKeyAnalysis as $tableName => $analysis) {
    echo "Table: {$tableName}\n";
    echo "  Foreign Key Performance Score: {$analysis['score']}/100\n";
    
    foreach ($analysis['issues'] as $issue) {
        echo "  Issue: {$issue['type']} - {$issue['description']}\n";
        echo "    Impact: {$issue['impact']}\n";
        echo "    Recommendation: {$issue['recommendation']}\n\n";
    }
}
```

**Foreign Key Performance Issues:**
- Missing indexes on foreign key columns
- Cascading deletes on large tables
- Complex foreign key chains
- Cross-database foreign keys
- Foreign keys to tables without primary keys

### 3. Data Type Efficiency Analysis

Evaluates data type choices for storage and performance efficiency:

```php
$dataTypeAnalysis = $analyzer->analyzeDataTypes($schema);

foreach ($dataTypeAnalysis['recommendations'] as $rec) {
    echo "Table: {$rec['table']}, Field: {$rec['field']}\n";
    echo "  Current Type: {$rec['current_type']}\n";
    echo "  Recommended Type: {$rec['recommended_type']}\n";
    echo "  Reason: {$rec['reason']}\n";
    echo "  Estimated Savings: {$rec['estimated_savings']}\n\n";
}
```

**Data Type Optimizations:**
- Oversized VARCHAR fields
- Using TEXT instead of VARCHAR for short strings
- Using INT instead of TINYINT for small numbers
- Inefficient date/time type usage
- Missing UNSIGNED attributes for positive numbers

### 4. Query Pattern Analysis

Analyzes common query patterns and their performance implications:

```php
$queryAnalysis = $analyzer->analyzeQueryPatterns($schema, [
    'common_joins' => $commonJoins,        // Array of frequently used joins
    'where_clauses' => $whereConditions,   // Common WHERE conditions
    'order_by_fields' => $orderByFields,   // Common ORDER BY fields
]);

foreach ($queryAnalysis['patterns'] as $pattern) {
    echo "Query Pattern: {$pattern['type']}\n";
    echo "  Frequency: {$pattern['frequency']}\n";
    echo "  Performance Score: {$pattern['score']}/100\n";
    echo "  Bottlenecks:\n";
    
    foreach ($pattern['bottlenecks'] as $bottleneck) {
        echo "    - {$bottleneck['description']}\n";
        echo "      Solution: {$bottleneck['solution']}\n";
    }
    echo "\n";
}
```

### 5. Table Structure Analysis

Evaluates overall table structure for performance:

```php
$structureAnalysis = $analyzer->analyzeTableStructure($schema);

foreach ($structureAnalysis as $tableName => $analysis) {
    echo "Table: {$tableName}\n";
    echo "  Structure Score: {$analysis['score']}/100\n";
    echo "  Row Size Estimate: {$analysis['estimated_row_size']} bytes\n";
    echo "  Index Overhead: {$analysis['index_overhead']}%\n";
    
    if (!empty($analysis['issues'])) {
        echo "  Structure Issues:\n";
        foreach ($analysis['issues'] as $issue) {
            echo "    - {$issue}\n";
        }
    }
}
```

## Detailed Analysis Results

### Performance Scoring System

The scoring system uses weighted components:

```php
// Understanding the score
$analysis = $analyzer->analyzeSchema($schema);

echo "Overall Score: {$analysis['score']}/100\n";
echo "Score Breakdown:\n";
echo "  Index Efficiency: {$analysis['component_scores']['indexes']}/100 (weight: 40%)\n";
echo "  Foreign Key Performance: {$analysis['component_scores']['foreign_keys']}/100 (weight: 30%)\n";
echo "  Data Type Efficiency: {$analysis['component_scores']['data_types']}/100 (weight: 20%)\n";
echo "  Table Structure: {$analysis['component_scores']['structure']}/100 (weight: 10%)\n";

// Performance level
$level = $analysis['performance_level']; // 'excellent', 'good', 'fair', 'poor'
echo "Performance Level: {$level}\n";
```

### Recommendation Priorities

Recommendations are prioritized by impact and effort:

```php
foreach ($analysis['recommendations'] as $rec) {
    echo "Priority: {$rec['priority']}\n";      // 'critical', 'high', 'medium', 'low'
    echo "Impact: {$rec['impact']}\n";          // 'high', 'medium', 'low'
    echo "Effort: {$rec['effort']}\n";          // 'low', 'medium', 'high'
    echo "Category: {$rec['category']}\n";      // 'indexes', 'foreign_keys', etc.
    echo "Message: {$rec['message']}\n";
    echo "Detailed Solution: {$rec['solution']}\n\n";
}
```

## Advanced Analysis

### Performance Trends

Track performance over time:

```php
class PerformanceTrendAnalyzer
{
    public function trackPerformance($schema, $identifier = null)
    {
        $analyzer = new PerformanceAnalyzer();
        $analysis = $analyzer->analyzeSchema($schema);
        
        $trend = [
            'timestamp' => time(),
            'identifier' => $identifier ?? date('Y-m-d'),
            'score' => $analysis['score'],
            'component_scores' => $analysis['component_scores'],
            'table_count' => count($schema->tables),
            'total_fields' => $this->countTotalFields($schema),
            'total_indexes' => $this->countTotalIndexes($schema),
        ];
        
        // Store trend data
        $this->storeTrendData($trend);
        
        return $trend;
    }
    
    public function getPerformanceTrend($days = 30)
    {
        $trends = $this->getTrendData($days);
        
        return [
            'score_trend' => $this->calculateTrend($trends, 'score'),
            'improvement_areas' => $this->identifyImprovementAreas($trends),
            'regression_areas' => $this->identifyRegressionAreas($trends),
        ];
    }
}

// Usage
$trendAnalyzer = new PerformanceTrendAnalyzer();
$currentTrend = $trendAnalyzer->trackPerformance($schema);
$trend = $trendAnalyzer->getPerformanceTrend(30);

echo "30-day trend: {$trend['score_trend']}% change\n";
```

### Comparative Analysis

Compare schemas or environments:

```php
$productionSchemas = new Schemas(['database' => 'production']);
$stagingSchemas = new Schemas(['database' => 'staging']);

$prodSchema = $productionSchemas->get();
$stagingSchema = $stagingSchemas->get();

$analyzer = new PerformanceAnalyzer();

$prodAnalysis = $analyzer->analyzeSchema($prodSchema);
$stagingAnalysis = $analyzer->analyzeSchema($stagingSchema);

// Compare results
$comparison = [
    'production_score' => $prodAnalysis['score'],
    'staging_score' => $stagingAnalysis['score'],
    'score_difference' => $prodAnalysis['score'] - $stagingAnalysis['score'],
    'production_issues' => count($prodAnalysis['recommendations']),
    'staging_issues' => count($stagingAnalysis['recommendations']),
];

echo "Performance Comparison:\n";
echo "  Production: {$comparison['production_score']}/100\n";
echo "  Staging: {$comparison['staging_score']}/100\n";
echo "  Difference: {$comparison['score_difference']} points\n";

if ($comparison['score_difference'] < -10) {
    echo "  WARNING: Production performance significantly lower than staging!\n";
}
```

### Workload-Specific Analysis

Analyze performance for specific workloads:

```php
$analyzer = new PerformanceAnalyzer();

// OLTP workload analysis
$oltpAnalysis = $analyzer->analyzeForWorkload($schema, 'oltp', [
    'query_patterns' => [
        'point_selects' => 0.6,      // 60% point selects
        'range_selects' => 0.2,      // 20% range selects
        'inserts' => 0.1,            // 10% inserts
        'updates' => 0.1,            // 10% updates
    ],
    'concurrent_users' => 1000,
    'response_time_target' => 100,   // 100ms target
]);

// OLAP workload analysis
$olapAnalysis = $analyzer->analyzeForWorkload($schema, 'olap', [
    'query_patterns' => [
        'aggregations' => 0.5,       // 50% aggregation queries
        'joins' => 0.3,              // 30% complex joins
        'full_scans' => 0.2,         // 20% full table scans
    ],
    'data_size' => 'large',
    'batch_processing' => true,
]);

// Compare workload-specific recommendations
echo "OLTP Recommendations:\n";
foreach ($oltpAnalysis['recommendations'] as $rec) {
    echo "  - {$rec['message']}\n";
}

echo "\nOLAP Recommendations:\n";
foreach ($olapAnalysis['recommendations'] as $rec) {
    echo "  - {$rec['message']}\n";
}
```

## Integration Examples

### Automated Performance Monitoring

```php
function monitorSchemaPerformance()
{
    $schemas = new Schemas();
    $schema = $schemas->get();
    
    $analyzer = new PerformanceAnalyzer([
        'scoring' => [
            'thresholds' => [
                'alert_threshold' => 60,  // Alert if score drops below 60
                'critical_threshold' => 40, // Critical alert below 40
            ],
        ],
    ]);
    
    $analysis = $analyzer->analyzeSchema($schema);
    
    // Check thresholds
    if ($analysis['score'] < 40) {
        sendCriticalAlert($analysis);
    } elseif ($analysis['score'] < 60) {
        sendWarningAlert($analysis);
    }
    
    // Log performance data
    logPerformanceMetrics([
        'timestamp' => time(),
        'score' => $analysis['score'],
        'recommendations' => count($analysis['recommendations']),
        'critical_issues' => count(array_filter(
            $analysis['recommendations'], 
            fn($r) => $r['priority'] === 'critical'
        )),
    ]);
    
    return $analysis;
}

// Schedule monitoring
// Run every hour in production
if (ENVIRONMENT === 'production') {
    $analysis = monitorSchemaPerformance();
}
```

### Performance-Driven Migration Planning

```php
function planPerformanceMigrations($schema)
{
    $analyzer = new PerformanceAnalyzer();
    $analysis = $analyzer->analyzeSchema($schema);
    
    // Group recommendations by implementation effort
    $migrationPlan = [
        'quick_wins' => [],      // Low effort, high impact
        'medium_effort' => [],   // Medium effort, medium-high impact
        'major_projects' => [],  // High effort, high impact
    ];
    
    foreach ($analysis['recommendations'] as $rec) {
        if ($rec['effort'] === 'low' && in_array($rec['impact'], ['high', 'medium'])) {
            $migrationPlan['quick_wins'][] = $rec;
        } elseif ($rec['effort'] === 'medium') {
            $migrationPlan['medium_effort'][] = $rec;
        } elseif ($rec['effort'] === 'high' && $rec['impact'] === 'high') {
            $migrationPlan['major_projects'][] = $rec;
        }
    }
    
    // Sort by priority within each category
    foreach ($migrationPlan as &$category) {
        usort($category, fn($a, $b) => 
            array_search($a['priority'], ['critical', 'high', 'medium', 'low']) <=>
            array_search($b['priority'], ['critical', 'high', 'medium', 'low'])
        );
    }
    
    return $migrationPlan;
}

// Generate migration plan
$plan = planPerformanceMigrations($schema);

echo "Migration Plan:\n";
echo "Quick Wins (implement first):\n";
foreach ($plan['quick_wins'] as $item) {
    echo "  - {$item['message']}\n";
}
```

### Performance Testing Integration

```php
function validatePerformanceImprovements($beforeSchema, $afterSchema)
{
    $analyzer = new PerformanceAnalyzer();
    
    $beforeAnalysis = $analyzer->analyzeSchema($beforeSchema);
    $afterAnalysis = $analyzer->analyzeSchema($afterSchema);
    
    $improvement = [
        'score_change' => $afterAnalysis['score'] - $beforeAnalysis['score'],
        'recommendations_change' => count($beforeAnalysis['recommendations']) - count($afterAnalysis['recommendations']),
        'component_improvements' => [],
    ];
    
    // Check component-level improvements
    foreach ($beforeAnalysis['component_scores'] as $component => $beforeScore) {
        $afterScore = $afterAnalysis['component_scores'][$component];
        $improvement['component_improvements'][$component] = $afterScore - $beforeScore;
    }
    
    // Validate improvements
    if ($improvement['score_change'] >= 5) {
        echo "✓ Performance improved by {$improvement['score_change']} points\n";
    } elseif ($improvement['score_change'] < -2) {
        echo "✗ Performance degraded by " . abs($improvement['score_change']) . " points\n";
        return false;
    }
    
    return true;
}

// Use in testing pipeline
$beforeSchema = loadSchemaSnapshot('before_migration');
$afterSchema = getCurrentSchema();

if (!validatePerformanceImprovements($beforeSchema, $afterSchema)) {
    echo "Migration caused performance regression!\n";
    exit(1);
}
```

## Best Practices

### 1. Regular Performance Audits
```php
// Weekly performance audit
if (date('w') == 1) { // Monday
    $analysis = (new PerformanceAnalyzer())->analyzeSchema($schema);
    generatePerformanceReport($analysis);
}
```

### 2. Performance Budgets
```php
// Set performance budgets
$performanceBudget = [
    'minimum_score' => 70,
    'maximum_recommendations' => 10,
    'critical_issues' => 0,
];

$analysis = $analyzer->analyzeSchema($schema);

// Check budget compliance
$budgetViolations = [];
if ($analysis['score'] < $performanceBudget['minimum_score']) {
    $budgetViolations[] = "Score below minimum: {$analysis['score']}/{$performanceBudget['minimum_score']}";
}

if (count($analysis['recommendations']) > $performanceBudget['maximum_recommendations']) {
    $budgetViolations[] = "Too many recommendations: " . count($analysis['recommendations']);
}
```

### 3. Environment-Specific Analysis
```php
// Different thresholds for different environments
$thresholds = [
    'production' => ['minimum_score' => 80, 'alert_on' => 'medium'],
    'staging' => ['minimum_score' => 70, 'alert_on' => 'high'],
    'development' => ['minimum_score' => 60, 'alert_on' => 'critical'],
];

$config = $thresholds[ENVIRONMENT];
$analyzer = new PerformanceAnalyzer(['thresholds' => $config]);
```

### 4. Performance Documentation
```php
/**
 * Performance Analysis Configuration
 * 
 * Scoring Weights:
 * - Indexes: 40% (most critical for query performance)
 * - Foreign Keys: 30% (impacts joins and referential integrity)
 * - Data Types: 20% (affects storage and memory usage)
 * - Structure: 10% (overall table design)
 * 
 * Thresholds:
 * - 90+: Excellent performance, minimal optimization needed
 * - 70-89: Good performance, minor optimizations beneficial
 * - 50-69: Fair performance, optimization recommended
 * - <50: Poor performance, optimization required
 */
```

The Performance Analysis system provides comprehensive insights into database performance characteristics, helping you make data-driven decisions about optimization priorities and ensuring your database scales effectively.
