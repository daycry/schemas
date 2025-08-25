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

namespace Daycry\Schemas\Exceptions;

use CodeIgniter\Exceptions\ExceptionInterface;
use RuntimeException;

class SchemasException extends RuntimeException implements ExceptionInterface
{
    public static function forMissingField($class, $field)
    {
        return new static(lang('Schemas.missingField', [$class, $field]));
    }

    public static function forUnsupportedHandler($class)
    {
        return new static(lang('Schemas.unsupportedHandler', [$class]));
    }

    public static function forMethodNotImplemented($class, $method)
    {
        return new static(lang('Schemas.methodNotImplemented', [$class, $method]));
    }

    public static function forNoSchema()
    {
        return new static(lang('Schemas.noSchema'));
    }

    public static function forReaderNotReady()
    {
        return new static(lang('Schemas.readerNotReady'));
    }

    public static function forInvalidPluginConfiguration(string $pluginName)
    {
        return new static("Invalid configuration for plugin '{$pluginName}'");
    }

    public static function forMissingPlugin(string $pluginName)
    {
        return new static("Plugin '{$pluginName}' not found");
    }

    public static function forPluginDependencyNotMet(string $pluginName, string $dependency)
    {
        return new static("Plugin '{$pluginName}' requires dependency '{$dependency}'");
    }

    public static function forIncompatiblePlugin(string $pluginName, string $version)
    {
        return new static("Plugin '{$pluginName}' is not compatible with Schemas version '{$version}'");
    }

    public static function forPluginAlreadyRegistered(string $pluginName)
    {
        return new static("Plugin '{$pluginName}' is already registered");
    }

    public static function forMissingSchema()
    {
        return new static('No schema available for archiving');
    }

    public static function forMissingArchiveHandler(string $mode)
    {
        return new static("Archive handler for mode '{$mode}' not found");
    }

    public static function forMissingReadHandler(string $extension)
    {
        return new static("Read handler for extension '{$extension}' not found");
    }
}
