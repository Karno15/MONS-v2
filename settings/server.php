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
        logServerMessage("New connection! ({$conn->resourceId})");
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        echo "Received message from client {$from->resourceId}: $msg\n";
        logClientMessage($from->resourceId, $msg);

        $data = json_decode($msg, true);

        if (!isValidToken($data)) {
            logServerMessage("Invalid token received from client {$from->resourceId}", 'ERROR');
            echo "Invalid token received from client {$from->resourceId}";
            $from->close();
            return;
        }

        if ($data && isset($data['type'])) {
            switch ($data['type']) {
                case 'grant_exp':
                    if (isset($data['pokemonId']) && isset($data['exp'])) {
                        $pokemonId = $data['pokemonId'];
                        $expgained = $data['exp'];
                        addExp($pokemonId, $expgained, $data['token']);


                        if (isset($response['redirect']) && $response['redirect'] === true) {
                            $from->send(json_encode(['logout' => true]));
                        }
                    }
                    break;
                case 'add_mon':
                    if (isset($data['pokedexId']) && isset($data['level'])) {
                        $pokedexId = $data['pokedexId'];
                        $level = $data['level'];
                        addMon($pokedexId, $level, $data['token']);
                    }
                    break;
                case 'release_pokemon':
                    if (isset($data['pokemonId'])) {
                        $pokemonId = $data['pokemonId'];
                        releasePokemon($pokemonId, $data['token']);
                    }
                    break;
                default:
                    echo "Invalid message type received from client {$from->resourceId}";
                    logServerMessage("Invalid message type received from client {$from->resourceId}", 'ERROR');

                    break;
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
        logServerMessage("Connection {$conn->resourceId} has disconnected");
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        logServerMessage("An error has occurred: {$e->getMessage()}", 'ERROR');

        $conn->close();
    }
}

$webSocketServer = new WsServer(new MyWebSocketServer());

$httpServer = new HttpServer($webSocketServer);

$server = IoServer::factory($httpServer, 8080);

echo "Server started at ws://127.0.0.1:8080\n";
logServerMessage("Server started at ws://127.0.0.1:8080");

$server->run();
