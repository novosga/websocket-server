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
     * @var int
     */
    private $id;
    
    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return Server::CLIENT_USER;
    }
    
    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
        
    /**
     * Emit update queue event
     */
    public function emitUpdateQueue()
    {
        $this->getSocket()->emit('update queue');
    }
    
    /**
     * Emit change user event
     */
    public function emitChangeUser()
    {
        $this->getSocket()->emit('change user');
    }
    
    /**
     * {@inheritdoc}
     */
    public function update(array $data)
    {
        $userId  = (int) Arrays::get($data, 'user');
        $unityId = (int) Arrays::get($data, 'unity');
        
        if (!$userId) {
            throw new \Exception('[User-Client] invalid user id');
        }
        
        if (!$unityId) {
            throw new \Exception('[User-Client] invalid unity id');
        }
        
        $this->id    = $userId;
        $this->unity = $unityId;
    }
}