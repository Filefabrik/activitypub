<?php

declare(strict_types=1);

/*
 * This file is part of the ActivityPhp package.
 *
 * Copyright (c) landrok at github.com/landrok
 *
 * For the full copyright and license information, please see
 * <https://github.com/landrok/activitypub/blob/master/LICENSE>.
 */

namespace ActivityPhp\Server\Configuration;

use Exception;

/**
 * Abstract methods for configurations classes
 */
abstract class AbstractConfiguration
{
    /**
     * Dispatch configuration parameters
     */
    public function __construct(array $params = [])
    {
        $this->setArray($params);
    }

    /**
     * Set configuration values by array
     *
     * @param array $settings
     *
     * @return void
     * @throws Exception
     */
    public function setArray(array $settings): void
    {
        foreach ($settings as $key => $value) {
            if (!is_string($key)) {
                throw new Exception(
                    'Configuration key must be a string',
                );
            } elseif (!isset($this->$key) && !property_exists($this, $key)) {
                throw new Exception(
                    sprintf("Configuration parameter '%s' does not exist", $key),
                );
            } else {
                // @todo Should be validated
                $this->$key = $value;
            }
        }
    }

    /**
     * Get a config value
     *
     * @param string $key
     *
     * @return mixed A configuration value
     * @throws Exception
     */
    public function get(string $key): mixed
    {
        if (isset($this->$key)) {
            return $this->$key;
        }

        throw new Exception(sprintf("'%s' parameter does not exist", $key));
    }

}
