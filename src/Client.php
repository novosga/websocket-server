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
     * @return int
     */
    public function getUnidade();
    
    /**
     * @param mixed $data
     */
    public function update($data);
    
    /**
     */
    public function registerOk();
}