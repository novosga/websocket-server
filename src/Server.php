<?php

namespace Novosga\Websocket;

use Novosga\Websocket\Client;
use PHPSocketIO\Socket;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Servidor websocket de eventos do sistema NovoSGA, utilizando socket.io protocol
 * para a comunicação com os clientes, e sem a necessidade de conexão com banco de dados
 * 
 * @author Rogério Lino <rogeriolino.com>
 */
class Server
{
    const CLIENT_PANEL  = 'panel';
    const CLIENT_TRIAGE = 'triage';
    const CLIENT_USER   = 'user';
    
    /**
     * @var Socket
     */
    public $socket;
    
    /**
     * @var Client[]
     */
    public $clients = [];
    
    /**
     * @var string
     */
    public $secret;
    
    /**
     * @var OutputInterface
     */
    public $output;
    
    public function __construct($secret, OutputInterface $output)
    {
        $this->secret = $secret;
        $this->output = $output;
    }
    
    /**
     * Output prints
     * @param string $messages
     */
    public function write(string $messages)
    {
        $info = date('[Y-m-d\TH:i:s]');
        
        $this->output->write("<info>{$info}</info> ");
        $this->output->writeln($messages);
    }
    
    /**
     * @param Socket $socket
     * @param \Novosga\Websocket\Address $address
     * @param array $data
     */
    public function onRegisterPanel(Socket $socket, Address $address, array $data = [])
    {
        $this->write("register panel: {$address}");
        $this->registerClient($socket, $address, self::CLIENT_PANEL, $data);
    }
    
    /**
     * @param Socket $socket
     * @param \Novosga\Websocket\Address $address
     * @param array $data
     */
    public function onRegisterTriage(Socket $socket, Address $address, array $data = [])
    {
        $this->write("register triage: {$address}");
        $this->registerClient($socket, $address, self::CLIENT_TRIAGE, $data);
    }
    
    /**
     * @param Socket $socket
     * @param \Novosga\Websocket\Address $address
     * @param array $data
     */
    public function onRegisterUser(Socket $socket, Address $address, array $data = [])
    {
        $secret = Arrays::get($data, 'secret');
        
        if ($secret !== $this->secret) {
            $this->write("invalid server secret. permission denied");
            $socket->disconnect();
            return;
        }
        
        $this->write("register user: {$address}");
        $this->registerClient($socket, $address, self::CLIENT_USER, $data);
    }
    
    /**
     * @param Socket $socket
     * @param \Novosga\Websocket\Address $address
     * @param array $data
     */
    public function onNewTicket(Socket $socket, Address $address, array $data = [])
    {
        $this->write("New ticket from {$address}: " . json_encode($data));
        
        $isUser   = !$this->isClient($socket, self::CLIENT_USER);
        $isTriage = !$this->isClient($socket, self::CLIENT_TRIAGE);

        if (!$isUser && !$isTriage) {
            return;
        }
        
        $unityId = (int) Arrays::get($data, 'unity');
        
        $this->emitUpdateQueue($unityId);
    }
    
    /**
     * @param Socket $socket
     * @param \Novosga\Websocket\Address $address
     * @param array $data
     */
    public function onChangeUser(Socket $socket, Address $address, array $data = [])
    {
        $this->write("Change user from {$address}: " . json_encode($data));
        
        if (!$this->isClient($socket, self::CLIENT_USER)) {
            return;
        }
        
        $unityId = (int) Arrays::get($data, 'unity');
        $userId  = (int) Arrays::get($data, 'user');
        
        $this->emitChangeUser($unityId, $userId);
    }
    
    
    /**
     * @param Socket $socket
     * @param \Novosga\Websocket\Address $address
     * @param array $data
     */
    public function onChangeTicket(Socket $socket, Address $address, array $data = [])
    {
        $this->write("Change ticket from {$address}: " . json_encode($data));
        
        if (!$this->isClient($socket, self::CLIENT_USER)) {
            return;
        }
        
        $unityId = (int) Arrays::get($data, 'unity');
        
        $this->emitUpdateQueue($unityId);
    }
    
    /**
     * @param Socket $socket
     * @param \Novosga\Websocket\Address $address
     * @param array $data
     */
    public function onCallTicket(Socket $socket, Address $address, array $data = [])
    {
        $this->write("Calling ticket from {$address}: " . json_encode($data));
        
        if (!$this->isClient($socket, self::CLIENT_USER)) {
            return;
        }
        
        $unityId   = (int) Arrays::get($data, 'unity');
        $serviceId = (int) Arrays::get($data, 'service');
        $hash      = Arrays::get($data, 'hash');

        $this->emitCallTicket($unityId, $serviceId, $hash);
        $this->emitUpdateQueue($unityId);
    }
    
    /**
     * @param Socket $socket
     * @param \Novosga\Websocket\Address $address
     * @param array $data
     */
    public function onClientUpdate(Socket $socket, Address $address, array $data = [])
    {
        $this->write(sprintf("client update: %s", json_encode($data)));

        if (!$this->isRegistered($socket)) {
            $this->write("Non registered client: {$address}");
            return;
        }

        $client = $this->getClientFromSocket($socket);
        $client->update($data);
    }
    
    /**
     * @param Socket $socket
     * @param \Novosga\Websocket\Address $address
     */
    public function onClientDisconnect(Socket $socket, Address $address)
    {
        $this->write("Client disconnected: {$address}");

        $this->removeClient($socket);
    }
    
    /**
     * @param int    $unityId
     * @param int    $serviceId
     * @param string $hash
     */
    private function emitCallTicket(int $unityId, int $serviceId, string $hash)
    {
        try {
            /* @var $panel PanelClient */
            foreach ($this->getPanels($unityId) as $panel) {
                $services = $panel->getServices();
                if (!is_array($services)) {
                    $services = [];
                }
                $this->write("panel {$panel->getAddress()->getIp()} - panel unity {$panel->getUnity()} - panel services: " . join(',', $services));
                if ($panel->getUnity() === $unityId && in_array($serviceId, $services)) {
                    $panel->emitCallTicket($unityId, $serviceId, $hash);
                    $this->write("Send alert to panel {$panel->getAddress()}");
                }
            }
        } catch (\Exception $e) {
            $this->write($e);
        }
    }
    
    /**
     * Envia para os usuários da mesma unidade o evento para atualizar a fila
     * @param Socket $socket
     * @param string $address
     * @param int    $unityId
     */
    private function emitUpdateQueue(int $unityId)
    {
        /* @var $user UserClient */
        foreach ($this->getUsers($unityId) as $user) {
            if ($user->getUnity() === $unityId) {
                $this->write("Send alert to user {$user->getAddress()}");
                $user->emitUpdateQueue();
            }
        }
    }
    
    /**
     * @param int $unityId
     * @param int $userId
     */
    private function emitChangeUser(int $unityId, int $userId)
    {
        /* @var $user UserClient */
        foreach ($this->getUsers($unityId) as $user) {
            if ($user->getUnity() === $unityId && $user->getId() === $userId) {
                $this->write("Send alert to user {$user->getAddress()}");
                $user->emitChangeUser();
            }
        }
    }
    
    /**
     * @param Socket $socket
     * @return bool
     */
    private function isRegistered(Socket $socket)
    {
        $client = $this->getClientFromSocket($socket);
        $isRegistered = $client !== null;
        
        return $isRegistered;
    }
    
    /**
     * @param Socket $socket
     * @param string $type
     * @return bool
     */
    private function isClient(Socket $socket, string $type)
    {
        $client = $this->getClientFromSocket($socket);
        
        if (!$client) {
            $this->write("Non registered client: socketId={$socket->id}");
            return false;
        }
        
        if ($client->getType() !== $type) {
            $this->write("Invalid client type: socketId={$socket->id}, type={$type}, expected: {$client->getType()}");
            return false;
        }
        
        return true;
    }
    
    /**
     * @param Socket $socket
     * @return Client|null
     */
    private function getClientFromSocket(Socket $socket)
    {
        $client = null;
        
        if (isset($this->clients[$socket->id])) {
            $client = $this->clients[$socket->id];
        }
        
        return $client;
    }
    
    /**
     * @paran int $unityId
     * @return PanelClient[]|\Generator
     */
    private function getPanels(int $unityId): \Generator
    {
        foreach ($this->clients as $client) {
            if ($client instanceof PanelClient && $client->getUnity() === $unityId) {
                yield $client;
            }
        }
    }
    
    /**
     * @param int $unityId
     * @return UserClient[]|\Generator
     */
    private function getUsers(int $unityId): \Generator
    {
        foreach ($this->clients as $client) {
            if ($client instanceof UserClient && $client->getUnity() === $unityId) {
                yield $client;
            }
        }
    }

    /**
     * @param Socket $socket
     * @param \Novosga\Websocket\Address $address
     * @param string $type
     * @param array  $data
     */
    private function registerClient(Socket $socket, Address $address, string $type, array $data)
    {
        try {
            $this->write(sprintf("trying to register new client: type=%s, data=%s", $type, json_encode($data)));

            $client = null;

            switch ($type) {
                case self::CLIENT_PANEL:
                    $client = new PanelClient($socket, $address, $data);
                    break;
                case self::CLIENT_TRIAGE:
                    $client = new TriageClient($socket, $address, $data);
                    break;
                case self::CLIENT_USER:
                    $client = new UserClient($socket, $address, $data);
                    break;
            }

            if (!$client) {
                throw new \Exception("Unknown client type: {$type}");
            }

            $this->clients[$client->getSocket()->id] = $client;

            $client->registerOk();
            $this->write("New client registered, total: " . count($this->clients));
        } catch (\Exception $e) {
            $this->write($e->getMessage());
            $this->write("Client register failed");
        }
    }
        
    /**
     * @param Socket $socket
     */
    private function removeClient(Socket $socket)
    {
        unset($this->clients[$socket->id]);
        $this->write("Client removed, total: " . count($this->clients));
    }
}
