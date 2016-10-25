<?php

/**
 * UserClient
 *
 * @author Rogério Lino <rogeriolino.com>
 */
class UserClient extends GenericClient
{
    public function sendUpdateQueue()
    {
        $this->getSocket()->emit('update queue');
    }
    
    /**
     * {@inheritdoc}
     */
    public function update($data)
    {
        
    }
}