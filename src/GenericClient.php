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
    protected $unity;

    public function __construct(Socket $socket, $address, array $data = [])
    {
        $this->socket = $socket;

        if ($address instanceof Address) {
            $this->address = $address;
        } else {
            $this->address = new Address($address);
        }
        
        $this->update($data);
    }

    /**
     * {@inheritdoc}
     */
    public function getSocket(): Socket
    {
        return $this->socket;
    }

    /**
     * {@inheritdoc}
     */
    public function getAddress(): Address
    {
        return $this->address;
    }

    /**
     * {@inheritdoc}
     */
    public function getUnity(): int
    {
        return $this->unity;
    }

    /**
     * {@inheritdoc}
     */
    public function registerOk()
    {
        $this->getSocket()->emit('register ok');
    }

}
