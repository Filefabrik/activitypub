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
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Logger configuration stack
 */
final class LoggerConfiguration extends AbstractConfiguration
{
    /**
     * @var string Logger class name
     */
    protected string $driver = NullLogger::class;

    /**
     * @var string Logger stream
     */
    protected string $stream = 'php://stdout';

    /**
     * @var string
     */
    protected string $channel = 'global';

    /**
     * Dispatch configuration parameters
     *
     * @param array $params
     */
    public function __construct(array $params = [])
    {
        parent::__construct($params);
    }

    /**
     * Create logger instance
     *
     * @return LoggerInterface
     * @throws Exception
     */
    public function createLogger(): LoggerInterface
    {
        if (!class_exists($this->driver)) {
            throw new Exception(
                "Logger driver does not exist. Given='{$this->driver}'",
            );
        }

        return new $this->driver();
    }
}
