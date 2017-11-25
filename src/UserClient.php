<?php

namespace Novosga\Websocket;

/**
 * UserClient
 *
 * @author Rogério Lino <rogeriolino.com>
 */
class UserClient extends GenericClient
{
    public function emitUpdateQueue()
    {
        $this->getSocket()->emit('update queue');
    }
    
    /**
     * {@inheritdoc}
     */
    public function update($data)
    {
        $this->unidade  = Arrays::get($data, 'unidade');
    }
}