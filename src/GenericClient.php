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
    
    /**
     * @var string
     */
    protected $unidade;
    
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

    /**
     * {@inheritdoc}
     */
    public function getUnidade()
    {
        return $this->unidade;
    }
    
    /**
     * {@inheritdoc}
     */
    public function registerOk() 
    {
        $this->getSocket()->emit('register ok');
    }
}