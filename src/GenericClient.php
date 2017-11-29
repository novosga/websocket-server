<?php

namespace Novosga\Websocket;

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
     * @var Address
     */
    private $address;
    
    /**
     * @var string
     */
    protected $unidade;
    
    public function __construct(Socket $socket, $address)
    {
        $this->socket = $socket;
        
        if ($address instanceof Address) {
            $this->address = $address;
        } else {
            $this->address = new Address($address);
        }
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
    public function getAddress()
    {
        return $this->address;
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