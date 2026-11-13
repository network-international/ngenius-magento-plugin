<?php

namespace NetworkInternational\NGenius\Helper;

/**
 * Helper class for managing module version.
 */
class Version
{
    /**
     * The current version of the module.
     *
     * @var string
     */
    public const MODULE_VERSION = '1.4.2';

    /**
     * Retrieves the module version.
     *
     * @return string The module version.
     */
    public function getVersion(): string
    {
        return self::MODULE_VERSION;
    }
}
