<?php
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

require_once __DIR__ . '/vendor/autoload.php';

class Chat implements MessageComponentInterface
{
    protected $clients;
    protected $usernames;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->usernames = [];
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        $this->usernames[$conn->resourceId] = '';

        echo "New connection! ({$conn->resourceId})\n";
        $conn->send("Welcome! Please enter your username:");
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $resourceId = $from->resourceId;

        if (empty($this->usernames[$resourceId])) {
            // User has not set their username yet
            $this->usernames[$resourceId] = $msg;
            $from->send("Hello " . $msg . "! You can now send messages.");
            echo "User " . $msg . " connected ({$resourceId})\n";
        } else {
            // User has set their username, broadcast message to all clients except sender
            foreach ($this->clients as $client) {
                if ($from !== $client) {
                    continue;
                }
                $client->send("You: {$msg}");
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $resourceId = $conn->resourceId;
        $username = $this->usernames[$resourceId];
        unset($this->usernames[$resourceId]);
        $this->clients->detach($conn);
        echo "Connection {$resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new Chat()
        )
    ),
    8080
);

// Start the server
$server->run();