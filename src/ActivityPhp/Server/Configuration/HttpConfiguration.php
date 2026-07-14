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

use ActivityPhp\Server;
use ActivityPhp\Version;
use Exception;

/**
 * Server HTTP configuration stack
 */
final class HttpConfiguration extends AbstractConfiguration
{
    /**
     * @var float|int HTTP timeout for request
     */
    protected int|float $timeout = 10.0;

    /**
     * @var string The User Agent.
     */
    protected $agent;

    /**
     * @var int Max number of retries
     */
    protected int $retries = 2;

    /**
     * @var int Number of seconds to sleep before retrying
     */
    protected int $sleep = 5;

    protected array $requestOptions = [];

    /**
     * Dispatch configuration parameters
     *
     * @param array $params
     */
    public function __construct(array $params = [])
    {
        parent::__construct($params);

        // Configure a default value for user agent
        if (is_null($this->agent)) {
            $this->agent = $this->getUserAgent();
        }
    }

    /**
     * Prepare a default value for user agent
     *
     * @throws Exception
     */
    private function getUserAgent(): string
    {
        $scheme = Server::server()
                        ->config('instance.scheme')
        ;
        $host   = Server::server()
                        ->config('instance.host')
        ;
        $port   = Server::server()
                        ->config('instance.port')
        ;

        if ($port == 443 && $scheme == 'https') {
            $port = null;
        }

        if ($port == 80 && $scheme == 'http') {
            $port = null;
        }

        $url = sprintf(
            '%s://%s%s',
            $scheme,
            $host,
            is_null($port) ? '' : ":{$port}",
        );

        return sprintf(
            '%s/%s (+%s)',
            Version::getRootNamespace(),
            Version::getVersion(),
            $url,
        );
    }
}
