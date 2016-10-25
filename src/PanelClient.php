<?php

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
        // TODO update client settings
        $this->services = $data;
    }
    
    public function sendTicket($senha)
    {
        // TODO: check service ID
        $data = [
            'id' => time(),
            'num_senha' => 1,
            'sig_senha' => 'A',
        ];
        $this->getSocket()->emit('new ticket', $data);
    }
}