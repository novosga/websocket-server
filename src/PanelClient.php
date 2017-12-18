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
    
    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return Server::CLIENT_PANEL;
    }
    
    /**
     * @return array|int[]
     */
    public function getServices(): array
    {
        return $this->services;
    }

    /**
     * @param array|int[] $services
     * @return $this
     */
    public function setServices(array $services): self
    {
        $this->services = $services;
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function update(array $data)
    {
        $unityId  = (int) Arrays::get($data, 'unity');
        $services = Arrays::get($data, 'services');
        
        if (!$unityId) {
            throw new \Exception('[Panel-Client] invalid unity id');
        }
        
        $this->unity    = $unityId;
        $this->services = [];
        
        if (is_array($services)) {
            foreach ($services as $service) {
                $this->services[] = (int) $service;
            }
        }
    }
    
    /**
     * Emit call ticket event
     * @param int $unityId
     * @param int $serviceId
     * @param string $hash
     */
    public function emitCallTicket(int $unityId, int $serviceId, string $hash)
    {
        $this->getSocket()->emit('call ticket', [
            'unity'   => $unityId,
            'service' => $serviceId,
            'hash'    => $hash,
        ]);
    }
}