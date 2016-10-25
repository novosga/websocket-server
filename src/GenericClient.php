<?php

use PHPSocketIO\Socket;

/**
 * GenericClient
 *
 * @author Rogério Lino <rogeriolino.com>
 */
abstract class GenericClient implements Client
{
    /**
     * @var Socket
     */
    private $socket;
    
    /**
     * @var string
     */
    private $ipAddress;
    
    public function __construct(Socket $socket, $ipAddress)
    {
        $this->socket = $socket;
        $this->ipAddress = $ipAddress;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getSocket()
    {
        return $this->socket;
    }

    /**
     * {@inheritdoc}
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    public function setSocket(Socket $socket)
    {
        $this->socket = $socket;
        return $this;
    }

    public function setIpAddress($ipAddress)
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }
}