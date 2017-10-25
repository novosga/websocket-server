<?php

use PHPSocketIO\Socket;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

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
     * @var ConsoleOutputInterface
     */
    public $output;
    
    public function __construct()
    {
        $this->output = new \Symfony\Component\Console\Output\ConsoleOutput();
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
    
    public function onRegisterPanel(Socket $socket, $ip, $data = [])
    {
        $this->write("register panel: {$ip}");
        $this->registerClient($socket, $ip, 'panel', $data);
    }
    
    public function onRegisterUser(Socket $socket, $ip, $data = [])
    {
        $this->write("register user: {$ip}");
        $this->registerClient($socket, $ip, 'user', $data);
    }
    
    public function onNewTicket(Socket $socket, $ip, $data = [])
    {
        $this->write("New ticket from {$ip}: " . json_encode($data));
        
        if (!$this->isRegistered($socket)) {
            $this->write("Non registered client: {$ip}");
            return;
        }
        
        $unidade = Arrays::get($data, 'unidade');
        
        $this->emitUpdateQueue($unidade);
    }
    
    public function onChangeTicket(Socket $socket, $ip, $data = [])
    {
        $this->write("Change ticket from {$ip}: " . json_encode($data));
        
        if (!$this->isRegistered($socket)) {
            $this->write("Non registered client: {$ip}");
            return;
        }
        
        $unidade = Arrays::get($data, 'unidade');
        
        $this->emitUpdateQueue($unidade);
    }
    
    public function onCallTicket(Socket $socket, $ip, $data = [])
    {
        $this->write("Calling ticket from {$ip}: " . json_encode($data));
        
        if (!$this->isRegistered($socket)) {
            $this->write("Non registered client: {$ip}");
            return;
        }
        
        $unidade = Arrays::get($data, 'unidade');
        $servico = Arrays::get($data, 'servico');
        $hash    = Arrays::get($data, 'hash');

        $this->emitCallTicket($unidade, $servico, $hash);
        $this->emitUpdateQueue($unidade);
    }
    
    public function onClientUpdate(Socket $socket, $ip, $data = [])
    {
        $this->write(sprintf("client update: %s", json_encode($data)));

        if (!$this->isRegistered($socket)) {
            $this->write("Non registered client: {$ip}");
            return;
        }

        $client = $this->getClientFromSocket($socket);
        $client->update($data);
    }
    
    public function onClientDisconnect(Socket $socket, $ip)
    {
        $this->write("Client disconnected: {$ip}");

        $this->removeClient($socket);
    }
    
    private function emitCallTicket($unidade, $servico, $hash)
    {
        foreach ($this->getPanels($unidade) as $panel) {
            $services = $panel->getServices();
            if (!is_array($services)) {
                $services = [];
            }
            $this->write("panel - unity {$panel->getUnidade()} - services " . join(',', $services));
            if ($panel->getUnidade() === $unidade && in_array($servico, $services)) {
                $this->write("Send alert to panel {$panel->getIpAddress()}");
                $panel->emitCallTicket($hash);
            }
        }
    }
    
    /**
     * Envia para os usuários da mesma unidade o evento para atualizar a fila
     * @param Socket $socket
     * @param string $ip
     * @param array $data
     */
    private function emitUpdateQueue($unidade)
    {
        foreach ($this->getUsers($unidade) as $user) {
            if ($user->getUnidade() === $unidade) {
                $this->write("Send alert to user {$user->getIpAddress()}");
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

    private function registerClient(Socket $socket, $ip, $type, $data)
    {
        if ($type === 'panel') {
            $client = new PanelClient($socket, $ip);
        } else {
            $client = new UserClient($socket, $ip);
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
