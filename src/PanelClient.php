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
        $this->unidade  = Arrays::get($data, 'unidade');
        $this->services = Arrays::get($data, 'servicos');
    }
    
    public function emitCallTicket($hash)
    {
        $this->getSocket()->emit('call ticket', $hash);
    }
}