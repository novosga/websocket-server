<?php

use PHPSocketIO\SocketIO;
use PHPSocketIO\Socket;
use Workerman\Lib\Timer;
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
    
    public function __construct(SocketIO $socket)
    {
        $this->socket = $socket;
        $this->output = new \Symfony\Component\Console\Output\ConsoleOutput();
    }
    
    public function start()
    {
        $server = $this;
        
        $this->socket->on('workerStart', function() use ($server) {
            Timer::add(1, function() use ($server) {
            });
        });
        
        $this->socket->on('connection', function(Socket $socket) use ($server) {
            $address = explode(':', $socket->conn->remoteAddress);
            $ip = $address[0];

            $server->write("<warning>Connection from: {$ip}</warning>");

            /**
             * Panel Client register
             * 
             * Evento recebido quando um painel se registra
             */
            $socket->on('register panel', function ($data) use ($socket, $server, $ip) {
                $server->registerPanel($socket, $ip);

                $server->write("Panel registered: {$ip}");
                $socket->emit('register ok');
            });

            /**
             * User Client register
             * 
             * Evento recebido quando usuário do sistema se registra
             */
            $socket->on('register user', function ($data) use ($socket, $server, $ip) {
                $server->registerUser($socket, $ip);

                $server->write("User registered: {$ip}");
                $socket->emit('register ok');
            });

            /**
             * User Client on triagem or redirecting on attendance [user only]
             * 
             * Evento disparado para avisar sobre um novo atendimento. Pode ser emitido
             * pela triagem ou quando um atendimento é redirecionado.
             */
            $socket->on('new ticket', function ($data) use ($socket, $server, $ip) {
                if (!$server->isRegistered($socket)) {
                    $server->write("Non registered client: {$ip}");
                    return;
                }
                
                $server->write("New ticket from {$ip}");
                $server->sendUpdateQueueAlert();
            });

            /**
             * User Client on attendance [user only]
             * 
             * Evento disparado quando o atendente está chamando uma senha. Por questão de segurança
             * o dado enviado é o hash do atendimento que o painel usará para puxar os dados do servidor
             */
            $socket->on('call ticket', function ($data) use ($socket, $server, $ip) {
                if (!$server->isRegistered($socket)) {
                    $server->write("Non registered client: {$ip}");
                    return;
                }
                
                $server->write("Calling ticket from {$ip}");

                $server->sendCallTicket($data);
                $server->sendUpdateQueueAlert();
            });

            /**
             * Client update [user and panel]
             * 
             * Evento disparado pelo cliente para atualizar suas configurações.
             */
            $socket->on('client update', function ($data) use ($socket, $server) {
                $server->write(sprintf("client update: %s\n", print_r($data, true)));
                
                $client = $server->getClientFromSocket($socket);
                $client->update($data);
            });
            
            /**
             * Socket disconnect
             * 
             * Atualiza lista de cliente
             */
            $socket->on('disconnect', function () use ($socket, $server) {
                $server->write("Client disconnected");
                
                $server->removeClient($socket);
            });

        });
    }
    
    public function isValidIp($ip)
    {
        // TODO check if the IP is registered
        return true;
    }
    
    private function removeClient(Socket $socket)
    {
        unset($this->clients[$socket->id]);
    }
    
    private function sendCallTicket($data)
    {
        foreach ($this->getPanels() as $panel) {
            $panel->sendTicket($data);
        }
    }
    
    private function sendUpdateQueueAlert()
    {
        foreach ($this->getUsers() as $user) {
            $user->sendUpdateQueue();
        }
    }
    
    private function isRegistered(Socket $socket)
    {
        $client = $this->getClientFromSocket($socket);
        $isRegistered = $client !== null;
        
        return $isRegistered;
    }
    
    /**
     * @param Socket $socket
     * @return Client
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
    private function getPanels()
    {
        foreach ($this->clients as $client) {
            if ($client instanceof PanelClient) {
                yield $client;
            }
        }
    }
    
    /**
     * @return UserClient[]
     */
    private function getUsers()
    {
        foreach ($this->clients as $client) {
            if ($client instanceof UserClient) {
                yield $client;
            }
        }
    }

    private function registerPanel(Socket $socket, $ip)
    {
        if (!$this->isValidIp($ip)) {
            $socket->disconnect(false);
            return;
        }
        
        $client = new PanelClient($socket, $ip);
        $this->registerClient($client);
    }
    
    private function registerUser(Socket $socket, $ip)
    {
        $client = new UserClient($socket, $ip);
        $this->registerClient($client);
    }
    
    private function registerClient(Client $client)
    {
        $this->clients[$client->getSocket()->id] = $client;
    }
    
    private function write($messages)
    {
        $info = date('[Y-m-d\TH:i:s]');
        
        $this->output->write("<info>{$info}</info> ");
        $this->output->writeln($messages);
    }
}