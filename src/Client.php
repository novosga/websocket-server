<?php

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
    public function getIpAddress();
    
    /**
     * @return Socket
     */
    public function getSocket();
    
    /**
     * @param mixed $data
     */
    public function update($data);
}