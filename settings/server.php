<?php
require __DIR__ . '/../ratchet/vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\WebSocket\WsServer;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;

// Define your WebSocket server class implementing the MessageComponentInterface
class MyWebSocketServer implements MessageComponentInterface
{
    protected $clients;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
    }

    // Called when a new connection is established
    public function onOpen(ConnectionInterface $conn)
    {
        // Store the new connection to send messages to later
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    // Called when a message is received from a client
    public function onMessage(ConnectionInterface $from, $msg)
    {
        echo "Received message from client {$from->resourceId}: $msg\n";
        // Check if the received message is "OK"
        if ($msg === "OK") {
            // Send "Sure" response back to the client
            $from->send("Sure");
        }
    }



    // Called when a connection is closed
    public function onClose(ConnectionInterface $conn)
    {
        // Remove the connection from the list of clients
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    // Called when an error occurs on a connection
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}

// Create a WebSocket server on localhost:8080
$webSocketServer = new WsServer(new MyWebSocketServer());

// Create an HTTP server
$httpServer = new HttpServer($webSocketServer);

// Create an instance of the server and run it
$server = IoServer::factory($httpServer, 8080);

echo "Server started at ws://127.0.0.1:8080\n";

// Run the event loop
$server->run();
