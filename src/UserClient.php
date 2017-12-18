<?php

namespace Novosga\Websocket;

/**
 * UserClient
 *
 * @author Rogério Lino <rogeriolino.com>
 */
class UserClient extends GenericClient
{
    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return Server::CLIENT_USER;
    }
    
    /**
     * Emit update queue event
     */
    public function emitUpdateQueue()
    {
        $this->getSocket()->emit('update queue');
    }
    
    /**
     * {@inheritdoc}
     */
    public function update(array $data)
    {
        $unityId = (int) Arrays::get($data, 'unity');
        
        if (!$unityId) {
            throw new \Exception('[User-Client] invalid unity id');
        }
        
        $this->unity = $unityId;
    }
}