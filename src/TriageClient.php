<?php

namespace Novosga\Websocket;

/**
 * TriageClient
 *
 * @author Rogério Lino <rogeriolino.com>
 */
class TriageClient extends GenericClient
{
    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return Server::CLIENT_TRIAGE;
    }
    
    /**
     * {@inheritdoc}
     */
    public function update(array $data)
    {
        $unityId  = (int) Arrays::get($data, 'unity');
        
        $this->unity = $unityId;
    }
}