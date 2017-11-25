<?php

namespace Novosga\Websocket;

use Exception;

/**
 * Address
 *
 * @author Rogério Lino <rogeriolino.com>
 */
class Address
{
    /**
     * @var string
     */
    private $ip;
    
    /**
     * @var int
     */
    private $port;
    
    public function __construct($ipOrFullAddress, $port = null)
    {
        if ($port > 0) {
            $this->ip   = $ipOrFullAddress;
            $this->port = $port;
        } else {
            $tokens = explode(':', $ipOrFullAddress);
            if (count($tokens) === 2) {
                $this->ip   = $tokens[0];
                $this->port = (int) $tokens[1];
            }
        }
        
        if ($this->port < 1024 || $this->port > 65535) {
            throw new Exception('Invalid port number');
        }
        
        if (!filter_var($this->ip, FILTER_VALIDATE_IP)) {
            throw new Exception('Invalid IP address');
        }
    }
    
    /**
     * @return string
     */
    public function getIp(): string
    {
        return $this->ip;
    }
    
    /**
     * @return string
     */
    public function getPort(): int
    {
        return $this->port;
    }
    
    public function __toString()
    {
        return "{$this->ip}:{$this->port}";
    }
}
