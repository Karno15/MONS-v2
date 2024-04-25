<?php

require 'conn.php';
require '../func.php';
require __DIR__ . '/../ratchet/vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\WebSocket\WsServer;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;

class MyWebSocketServer implements MessageComponentInterface
{
    protected $clients;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        echo "Received message from client {$from->resourceId}: $msg\n";
        $data = json_decode($msg, true);

        if (!isValidToken($data)) {
            $from->close();
            return;
        }

        if ($data && isset($data['type'])) {
            switch ($data['type']) {
                case 'grant_exp':
                    if (isset($data['pokemonId']) && isset($data['exp'])) {
                        $pokemonId = $data['pokemonId'];
                        $expgained = $data['exp'];
                        addExp($pokemonId, $expgained);
                    }
                    break;
                case 'add_mon':
                    if (isset($data['pokedexId']) && isset($data['level'])) {
                        $pokedexId = $data['pokedexId'];
                        $level = $data['level'];
                        addMon($pokedexId, $level, $data['token']);
                    }
                    break;
                default:
                    echo "Invalid!";
                    break;
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}

$webSocketServer = new WsServer(new MyWebSocketServer());

$httpServer = new HttpServer($webSocketServer);

$server = IoServer::factory($httpServer, 8080);

echo "Server started at ws://127.0.0.1:8080\n";

$server->run();
