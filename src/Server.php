<?php

namespace Novosga\Websocket;

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
    /**
     * @var Socket
     */
    public $socket;
    
    /**
     * @var Client[]
     */
    public $clients = [];
    
    /**
     * @var OutputInterface
     */
    public $output;
    
    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }
    
    /**
     * Output prints
     * @param string $messages
     */
    public function write($messages)
    {
        $info = date('[Y-m-d\TH:i:s]');
        
        $this->output->write("<info>{$info}</info> ");
        $this->output->writeln($messages);
    }
    
    public function onRegisterPanel(Socket $socket, Address $address, $data = [])
    {
        $this->write("register panel: {$address}");
        $this->registerClient($socket, $address, 'panel', $data);
    }
    
    public function onRegisterUser(Socket $socket, Address $address, $data = [])
    {
        $this->write("register user: {$address}");
        $this->registerClient($socket, $address, 'user', $data);
    }
    
    public function onNewTicket(Socket $socket, Address $address, $data = [])
    {
        $this->write("New ticket from {$address}: " . json_encode($data));
        
        if (!$this->isRegistered($socket)) {
            $this->write("Non registered client: {$address}");
            return;
        }
        
        $unidade = Arrays::get($data, 'unidade');
        
        $this->emitUpdateQueue($unidade);
    }
    
    public function onChangeTicket(Socket $socket, Address $address, $data = [])
    {
        $this->write("Change ticket from {$address}: " . json_encode($data));
        
        if (!$this->isRegistered($socket)) {
            $this->write("Non registered client: {$address}");
            return;
        }
        
        $unidade = Arrays::get($data, 'unidade');
        
        $this->emitUpdateQueue($unidade);
    }
    
    public function onCallTicket(Socket $socket, Address $address, $data = [])
    {
        $this->write("Calling ticket from {$address}: " . json_encode($data));
        
        if (!$this->isRegistered($socket)) {
            $this->write("Non registered client: {$address}");
            return;
        }
        
        $unidade = (int) Arrays::get($data, 'unidade');
        $servico = (int) Arrays::get($data, 'servico');
        $hash    = Arrays::get($data, 'hash');

        $this->emitCallTicket($unidade, $servico, $hash);
//        $this->emitUpdateQueue($unidade);
    }
    
    public function onClientUpdate(Socket $socket, Address $address, $data = [])
    {
        $this->write(sprintf("client update: %s", json_encode($data)));

        if (!$this->isRegistered($socket)) {
            $this->write("Non registered client: {$address}");
            return;
        }

        $client = $this->getClientFromSocket($socket);
        $client->update($data);
    }
    
    public function onClientDisconnect(Socket $socket, Address $address)
    {
        $this->write("Client disconnected: {$address}");

        $this->removeClient($socket);
    }
    
    private function emitCallTicket($unidade, $servico, $hash)
    {
        try {
            foreach ($this->getPanels($unidade) as $panel) {
                $services = $panel->getServices();
                if (!is_array($services)) {
                    $services = [];
                }
                $this->write("panel {$panel->getAddress()->getIp()} - panel unity {$panel->getUnidade()} - panel services: " . join(',', $services));
                if ($panel->getUnidade() === $unidade && in_array($servico, $services)) {
                    $panel->emitCallTicket($hash);
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
     * @param array $data
     */
    private function emitUpdateQueue($unidade)
    {
        foreach ($this->getUsers($unidade) as $user) {
            if ($user->getUnidade() === $unidade) {
                $this->write("Send alert to user {$user->getAddress()}");
                $user->emitUpdateQueue();
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
     * @return PanelClient[]
     */
    private function getPanels($unidade)
    {
        foreach ($this->clients as $client) {
            if ($client instanceof PanelClient && $client->getUnidade() === $unidade) {
                yield $client;
            }
        }
    }
    
    /**
     * @return UserClient[]
     */
    private function getUsers($unidade)
    {
        foreach ($this->clients as $client) {
            if ($client instanceof UserClient && $client->getUnidade() === $unidade) {
                yield $client;
            }
        }
    }

    private function registerClient(Socket $socket, Address $address, $type, $data)
    {
        if ($type === 'panel') {
            $client = new PanelClient($socket, $address);
        } else {
            $client = new UserClient($socket, $address);
        }
        
        $this->clients[$client->getSocket()->id] = $client;
        
        $client->registerOk();
        
        if ($data) {
            $this->write(sprintf("client update: %s", json_encode($data)));
            $client->update($data);
        }
        
        return $client;
    }
        
    /**
     * @param Socket $socket
     */
    private function removeClient(Socket $socket)
    {
        unset($this->clients[$socket->id]);
    }
}
