<?php

namespace Novosga\Websocket;

/**
 * PanelClient
 *
 * @author Rogério Lino <rogeriolino.com>
 */
class PanelClient extends GenericClient
{
    /**
     * @var array
     */
    private $services = [];
    
    public function getServices()
    {
        return $this->services;
    }

    public function setServices($services)
    {
        $this->services = $services;
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function update($data)
    {
        $this->unidade  = (int) Arrays::get($data, 'unidade');
        $services       = Arrays::get($data, 'servicos');
        $this->services = [];
        
        if (is_array($services)) {
            foreach ($services as $service) {
                $this->services[] = (int) $service;
            }
        }
    }
    
    public function emitCallTicket($hash)
    {
        $this->getSocket()->emit('call ticket', $hash);
    }
}