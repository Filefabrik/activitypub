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

namespace ActivityPhp\Server;

use ActivityPhp\Server;
use ActivityPhp\Server\Actor\ActorFactory;
use ActivityPhp\Server\Http\WebFingerFactory as WebFinger;
use ActivityPhp\Type\Extended\AbstractActor;
use ActivityPhp\Type\Util;
use Exception;

/**
 * A server-oriented actor object
 */
final class Actor
{
    /**
     * @var Server
     */
    protected Server $server;

    /**
     * @var AbstractActor
     */
    protected AbstractActor $actor;

    /**
     * Construct an Actor instance based upon a WebFinger discovery if
     * a handle-like is provided. Otherwise, it checks an ActivityPhp
     * profile id if it's an URL.
     *
     * @param string $handle URL or a WebFinger handle
     * @param Server $server
     *
     * @throws Exception
     */
    public function __construct(string $handle, Server $server)
    {
        $this->server = $server;
        $url          = null;

        WebFinger::setServer($this->server);

        // Is a valid handle?
        if ($this->isHandle($handle)) {
            // testing only
            $scheme    = $this->server->config('instance.debug')
                ? 'http' : 'https';
            $webfinger = WebFinger::get($handle, $scheme);
            $url       = $webfinger->getProfileId();
            // Is an id?
        } elseif (Util::validateUrl($handle)) {
            $url = $handle;
        }

        if (is_null($url)) {
            throw new Exception(
                "Invalid Actor handle: ".print_r($handle, true),
            );
        }

        $this->createActor($url);
    }

    /**
     * Check that a string is a valid handle
     *
     * @param string $handle
     *
     * @return bool
     */
    private function isHandle(string $handle): bool
    {
        return (bool)preg_match(
            '/^@?(?P<user>[\w\.\-]+)@(?P<host>[\w\.\-]+)(?P<port>:[\d]+)?$/',
            $handle,
        );
    }

    /**
     * Get ActivityStream Actor
     *
     * @param null $property
     *
     * @return AbstractActor|string|array
     *
     */
    public function get($property = null): AbstractActor|string|array
    {
        if (is_null($property)) {
            return $this->actor;
        }

        return $this->actor->get($property);
    }

    /**
     * Build a profile
     *
     * @param string $url A profile id
     *
     * @throws Exception
     */
    private function createActor(string $url): void
    {
        ActorFactory::setServer($this->server);
        $this->actor = ActorFactory::create($url);
    }

    /**
     * Get Actor's public key PEM
     *
     * @return string|null
     */
    public function getPublicKeyPem()
    {
        if (
            !isset($this->actor->publicKey)
            || !is_array($this->actor->publicKey)
            || !isset($this->actor->publicKey['publicKeyPem'])
        ) {
            $this->server->logger()
                         ->info(
                             'Public key not found',
                             [$this->actor->toArray()],
                         )
            ;

            return false;
        }

        return $this->actor->publicKey['publicKeyPem'];
    }

    /**
     * Get WebFinger bound to a profile
     *
     * @return Http\WebFinger
     */
    public function webfinger()
    {
        // testing only
        $scheme = $this->server->config('instance.debug')
            ? 'http' : 'https';

        $port = !is_null(parse_url($this->actor->id, PHP_URL_PORT))
            ? ':'.parse_url($this->actor->id, PHP_URL_PORT)
            : '';

        $handle = sprintf(
            '%s@%s%s',
            $this->actor->preferredUsername,
            parse_url($this->actor->id, PHP_URL_HOST),
            $port,
        );

        return WebFinger::get($handle, $scheme);
    }
}
