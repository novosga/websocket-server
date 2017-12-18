<?php

namespace Novosga\Websocket;

use PHPSocketIO\Socket;
use PHPSocketIO\SocketIO;
use Symfony\Component\Console\Output\OutputInterface;
use Workerman\Worker;

/**
 * Servidor websocket de eventos do sistema NovoSGA, utilizando socket.io protocol
 * para a comunicação com os clientes, e sem a necessidade de conexão com banco de dados
 * 
 * @author Rogério Lino <rogeriolino.com>
 */
class Command
{
    public function run(string $serverSecret, OutputInterface $output)
    {
        $io = new SocketIO(2020);
        $server = new Server($serverSecret, $output);

        $io->on('connection', function (Socket $socket) use ($server) {
            $address = new Address($socket->conn->remoteAddress);

            $server->write("<comment>Connection from: {$address}</comment>");

            /**
             * Panel Client register
             * 
             * Evento recebido quando um painel se registra
             */
            $socket->on('register panel', function ($data = []) use ($socket, $server, $address) {
                $server->onRegisterPanel($socket, $address, $data);
            });

            /**
             * User Client register
             * 
             * Evento recebido quando usuário do sistema se registra
             */
            $socket->on('register user', function ($data = []) use ($socket, $server, $address) {
                $server->onRegisterUser($socket, $address, $data);
            });

            /**
             * User Client on triagem or redirecting on attendance [user only]
             * 
             * Evento disparado para avisar sobre um novo atendimento. Pode ser emitido
             * pela triagem ou quando um atendimento é redirecionado.
             */
            $socket->on('new ticket', function ($data = []) use ($socket, $server, $address) {
                $server->onNewTicket($socket, $address, $data);
            });

            /**
             * User Client on monitor emitted when cancel or transfer ticket [user only]
             * 
             * Evento disparado para avisar sobre uma alteração no atendimento. É emitido
             * pelo monitor quando um atendimento é cancelado ou transferido.
             */
            $socket->on('change ticket', function ($data = []) use ($socket, $server, $address) {
                $server->onChangeTicket($socket, $address, $data);
            });

            /**
             * User Client on attendance [user only]
             * 
             * Evento disparado quando o atendente está chamando uma senha. Por questão de segurança
             * o dado enviado é o hash do atendimento que o painel usará para puxar os dados do servidor
             */
            $socket->on('call ticket', function ($data = []) use ($socket, $server, $address) {
                $server->onCallTicket($socket, $address, $data);
            });

            /**
             * Client update [user and panel]
             * 
             * Evento disparado pelo cliente para atualizar suas configurações.
             */
            $socket->on('client update', function ($data = []) use ($socket, $server, $address) {
                $server->onClientUpdate($socket, $address, $data);
            });

            /**
             * Socket disconnect
             * 
             * Atualiza lista de cliente
             */
            $socket->on('disconnect', function () use ($socket, $server, $address) {
                $server->onClientDisconnect($socket, $address);
            });

        });

        // Run worker
        Worker::runAll();
    }
}