<?php

namespace Novosga\Websocket;

use PHPSocketIO\Socket;

/**
 * Client
 *
 * @author Rogério Lino <rogeriolino.com>
 */
interface Client
{
    /**
     * @return string
     */
    public function getType(): string;
    /**
     * @return Address
     */
    public function getAddress(): Address;
    
    /**
     * @return Socket
     */
    public function getSocket(): Socket;
    
    /**
     * @return int
     */
    public function getUnity(): int;
    
    /**
     * @param array $data
     */
    public function update(array $data);
    
    /**
     * Emit register ok event
     */
    public function registerOk();
}