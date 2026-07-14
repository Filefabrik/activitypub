<?php

/*
 * This file is part of the ActivityPhp package.
 *
 * Copyright (c) landrok at github.com/landrok
 *
 * For the full copyright and license information, please see
 * <https://github.com/landrok/activitypub/blob/master/LICENSE>.
 */

namespace ActivityPhp\Server\Http;

use ActivityPhp\Server;
use ActivityPhp\Server\Cache\CacheHelper;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Request handler
 */
final class Request
{
    public const string HTTP_HEADER_ACCEPT = 'application/activity+json,application/ld+json,application/json';

    /**
     * @var string HTTP method
     */
    protected string $method = 'GET';

    /**
     * Allowed HTTP methods
     *
     * @var string[]
     */
    protected array $allowedMethods = [
        'GET',
        'POST',
    ];

    /**
     * HTTP client
     *
     * @var Client
     */
    protected Client $client;

    /**
     * Number of allowed retries
     *
     * -1: unlimited
     * 0 : never retry
     * >0: throw exception after this number of retries
     */
    protected int $maxRetries = 0;

    /**
     * Number of seconds to wait before retrying
     */
    protected int $sleepBeforeRetry = 5;

    /**
     * Current retries counter
     */
    protected int $retryCounter = 0;

    /**
     * Set HTTP client
     *
     * @param float|int $timeout
     * @param string    $agent
     * @param array     $requestOptions guzzle request options
     */
    public function __construct(
        float|int $timeout = 10.0,
        string $agent = '',
        public private(set) array $requestOptions = [],
    ) {
        $headers = ['Accept' => self::HTTP_HEADER_ACCEPT];

        if ($agent) {
            $headers['User-Agent'] = $agent;
        }

        $this->client = new Client([
            'timeout' => $timeout,
            'headers' => $headers,
        ]);
    }

    /**
     * Set Max retries after a sleeping time
     */
    public function setMaxRetries(int $maxRetries, int $sleepBeforeRetry = 5): self
    {
        $this->maxRetries       = $maxRetries;
        $this->sleepBeforeRetry = $sleepBeforeRetry;

        return $this;
    }

    /**
     * Execute a GET request
     *
     * @param string $url
     *
     * @return string
     * @throws GuzzleException
     * @throws Exception
     */
    public function get(string $url): string
    {
        if (CacheHelper::has($url)) {
            return CacheHelper::get($url);
        }

        try {
            $content = $this->client->get($url, $this->requestOptions)
                                    ->getBody()
                                    ->getContents()
            ;
        } catch (Exception $e) {
            Server::server()
                  ->logger()
                  ->error(
                      __METHOD__.':failure',
                      ['url' => $url, 'message' => $e->getMessage()],
                  )
            ;
            if (
                $this->maxRetries === -1
                || $this->retryCounter < $this->maxRetries
            ) {
                $this->retryCounter++;
                Server::server()
                      ->logger()
                      ->info(
                          __METHOD__.':retry#'.$this->retryCounter,
                          ['url' => $url],
                      )
                ;
                sleep($this->sleepBeforeRetry);

                return $this->get($url);
            }

            throw new Exception($e->getMessage());
        }

        CacheHelper::set($url, $content);

        $this->retryCounter = 0;

        return $content;
    }

    /**
     * Get HTTP methods
     *
     * @return string
     */
    protected function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Set HTTP methods
     *
     * @param string $method
     */
    protected function setMethod(string $method): void
    {
        if (in_array($method, $this->allowedMethods)) {
            $this->method = $method;
        }
    }
}
