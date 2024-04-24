<?php
require 'conn.php';
require '../func.php';
require __DIR__ . '/../ratchet/vendor/autoload.php';

session_start();

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
        global $conn;
        echo "Received message from client {$from->resourceId}: $msg\n";
        $data = json_decode($msg, true);
        if ($data && isset($data['type']) && $data['type'] === 'grant_exp' && isset($data['pokemonId']) && isset($data['exp'])) {
            $pokemonId = $data['pokemonId'];
            $expgained = $data['exp'];

            addExp($pokemonId,$expgained);
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
