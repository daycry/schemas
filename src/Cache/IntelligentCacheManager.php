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

namespace Daycry\Schemas\Cache;

use CodeIgniter\Cache\CacheInterface;
use Daycry\Schemas\Structures\Schema;

/**
 * Intelligent Cache Manager
 * 
 * Provides advanced caching with invalidation, versioning, and differential updates
 */
class IntelligentCacheManager
{
    protected CacheInterface $cache;
    protected string $prefix = 'daycry_schemas_';
    protected string $versionKey = 'schema_version';
    protected string $metadataKey = 'schema_metadata';

    public function __construct(?CacheInterface $cache = null)
    {
        $this->cache = $cache ?? cache();
    }

    /**
     * Store schema with versioning and metadata
     */
    public function store(string $key, Schema $schema, int $ttl = 3600, array $metadata = []): bool
    {
        $version = $this->getNextVersion($key);
        $fullKey = $this->getVersionedKey($key, $version);
        
        // Store the schema
        $stored = $this->cache->save($fullKey, $schema, $ttl);
        
        if ($stored) {
            // Update version pointer
            $this->cache->save($this->prefix . $key . '_current_version', $version, $ttl);
            
            // Store metadata
            $metadata['timestamp'] = time();
            $metadata['version'] = $version;
            $metadata['ttl'] = $ttl;
            $this->cache->save($this->prefix . $key . '_metadata', $metadata, $ttl);
            
            // Clean old versions (keep last 3)
            $this->cleanOldVersions($key, $version);
        }
        
        return $stored;
    }

    /**
     * Retrieve schema by key
     */
    public function get(string $key): ?Schema
    {
        $version = $this->getCurrentVersion($key);
        if ($version === null) {
            return null;
        }
        
        $fullKey = $this->getVersionedKey($key, $version);
        return $this->cache->get($fullKey);
    }

    /**
     * Retrieve specific version of schema
     */
    public function getVersion(string $key, int $version): ?Schema
    {
        $fullKey = $this->getVersionedKey($key, $version);
        return $this->cache->get($fullKey);
    }

    /**
     * Check if cache exists and is valid
     */
    public function exists(string $key): bool
    {
        $version = $this->getCurrentVersion($key);
        if ($version === null) {
            return false;
        }
        
        $fullKey = $this->getVersionedKey($key, $version);
        return $this->cache->get($fullKey) !== null;
    }

    /**
     * Invalidate cache by key
     */
    public function invalidate(string $key): bool
    {
        $version = $this->getCurrentVersion($key);
        if ($version === null) {
            return true;
        }
        
        // Delete current version
        $fullKey = $this->getVersionedKey($key, $version);
        $this->cache->delete($fullKey);
        
        // Delete version pointer and metadata
        $this->cache->delete($this->prefix . $key . '_current_version');
        $this->cache->delete($this->prefix . $key . '_metadata');
        
        return true;
    }

    /**
     * Invalidate by tags (simulate tagging for drivers that don't support it)
     */
    public function invalidateByTag(string $tag): bool
    {
        $taggedKeys = $this->getKeysByTag($tag);
        
        foreach ($taggedKeys as $key) {
            $this->invalidate($key);
        }
        
        return true;
    }

    /**
     * Tag a cache entry
     */
    public function tag(string $key, array $tags): bool
    {
        foreach ($tags as $tag) {
            $tagKey = $this->prefix . 'tag_' . $tag;
            $taggedKeys = $this->cache->get($tagKey) ?? [];
            
            if (!in_array($key, $taggedKeys, true)) {
                $taggedKeys[] = $key;
                $this->cache->save($tagKey, $taggedKeys, 86400); // 24 hours
            }
        }
        
        return true;
    }

    /**
     * Get cache metadata
     */
    public function getMetadata(string $key): ?array
    {
        return $this->cache->get($this->prefix . $key . '_metadata');
    }

    /**
     * Check if cache has expired based on database changes
     */
    public function hasExpired(string $key, array $currentDbInfo = []): bool
    {
        $metadata = $this->getMetadata($key);
        
        if (!$metadata) {
            return true;
        }
        
        // Check TTL expiration
        if (isset($metadata['timestamp'], $metadata['ttl'])) {
            if (time() > ($metadata['timestamp'] + $metadata['ttl'])) {
                return true;
            }
        }
        
        // Check database modification time if available
        if (!empty($currentDbInfo) && isset($metadata['db_info'])) {
            return $this->hasDbChanged($metadata['db_info'], $currentDbInfo);
        }
        
        return false;
    }

    /**
     * Store differential update (only changed tables)
     */
    public function storeDifferential(string $key, array $changedTables, int $ttl = 3600): bool
    {
        $diffKey = $this->prefix . $key . '_diff_' . time();
        return $this->cache->save($diffKey, $changedTables, $ttl);
    }

    /**
     * Get list of available versions
     */
    public function getVersions(string $key): array
    {
        $versions = [];
        $currentVersion = $this->getCurrentVersion($key);
        
        if ($currentVersion === null) {
            return $versions;
        }
        
        // Check backwards for existing versions
        for ($i = $currentVersion; $i >= max(1, $currentVersion - 10); $i--) {
            $fullKey = $this->getVersionedKey($key, $i);
            if ($this->cache->get($fullKey) !== null) {
                $versions[] = $i;
            }
        }
        
        return $versions;
    }

    /**
     * Get current version number
     */
    protected function getCurrentVersion(string $key): ?int
    {
        return $this->cache->get($this->prefix . $key . '_current_version');
    }

    /**
     * Get next version number
     */
    protected function getNextVersion(string $key): int
    {
        $current = $this->getCurrentVersion($key);
        return ($current ?? 0) + 1;
    }

    /**
     * Get versioned cache key
     */
    protected function getVersionedKey(string $key, int $version): string
    {
        return $this->prefix . $key . '_v' . $version;
    }

    /**
     * Clean old versions keeping only the last few
     */
    protected function cleanOldVersions(string $key, int $currentVersion, int $keepVersions = 3): void
    {
        $deleteVersion = $currentVersion - $keepVersions;
        
        if ($deleteVersion > 0) {
            $oldKey = $this->getVersionedKey($key, $deleteVersion);
            $this->cache->delete($oldKey);
        }
    }

    /**
     * Get keys associated with a tag
     */
    protected function getKeysByTag(string $tag): array
    {
        $tagKey = $this->prefix . 'tag_' . $tag;
        return $this->cache->get($tagKey) ?? [];
    }

    /**
     * Check if database structure has changed
     */
    protected function hasDbChanged(array $oldInfo, array $newInfo): bool
    {
        // Compare table counts
        if (($oldInfo['table_count'] ?? 0) !== ($newInfo['table_count'] ?? 0)) {
            return true;
        }
        
        // Compare modification times if available
        if (isset($oldInfo['last_modified'], $newInfo['last_modified'])) {
            return $oldInfo['last_modified'] < $newInfo['last_modified'];
        }
        
        // Compare table schemas if available
        if (isset($oldInfo['tables'], $newInfo['tables'])) {
            return $oldInfo['tables'] !== $newInfo['tables'];
        }
        
        return false;
    }
}
