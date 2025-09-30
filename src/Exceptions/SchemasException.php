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
    public static function forMissingField(string $class, string $field): self
    {
        return new self(lang('Schemas.missingField', [$class, $field]));
    }

    public static function forUnsupportedHandler(string $class): self
    {
        return new self(lang('Schemas.unsupportedHandler', [$class]));
    }

    public static function forMethodNotImplemented(string $class, string $method): self
    {
        return new self(lang('Schemas.methodNotImplemented', [$class, $method]));
    }

    public static function forNoSchema(): self
    {
        return new self(lang('Schemas.noSchema'));
    }

    public static function forReaderNotReady(): self
    {
        return new self(lang('Schemas.readerNotReady'));
    }

    public static function forInvalidPluginConfiguration(string $pluginName): self
    {
        return new self("Invalid configuration for plugin '{$pluginName}'");
    }

    public static function forMissingPlugin(string $pluginName): self
    {
        return new self("Plugin '{$pluginName}' not found");
    }

    public static function forPluginDependencyNotMet(string $pluginName, string $dependency): self
    {
        return new self("Plugin '{$pluginName}' requires dependency '{$dependency}'");
    }

    public static function forIncompatiblePlugin(string $pluginName, string $version): self
    {
        return new self("Plugin '{$pluginName}' is not compatible with Schemas version '{$version}'");
    }

    public static function forPluginAlreadyRegistered(string $pluginName): self
    {
        return new self("Plugin '{$pluginName}' is already registered");
    }

    public static function forMissingSchema(): self
    {
        return new self('No schema available for archiving');
    }

    public static function forMissingArchiveHandler(string $mode): self
    {
        return new self("Archive handler for mode '{$mode}' not found");
    }

    public static function forMissingReadHandler(string $extension): self
    {
        return new self("Read handler for extension '{$extension}' not found");
    }
}
