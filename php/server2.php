<?php
require __DIR__ . '/vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

class ChatRoom implements MessageComponentInterface
{
    protected $clients;
    protected $usernames;
    protected $lastUserId;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->usernames = array();
        $this->lastUserId = 0;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        // Store the new connection in $clients
        $this->clients->attach($conn);

        // Assign the user a unique ID and ask for their name
        $userId = ++$this->lastUserId;
        $conn->userId = $userId;
        $conn->send('Welcome! Please enter your name:');
		echo sprintf("New connection! ({$conn->resourceId})\n");
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $numRecv = count($this->clients) - 1;

        // Check if the user has already set their name
        if (!isset($from->username)) {
            $from->username = htmlspecialchars($msg);

            // Store the username for this connection
            $this->usernames[$from->userId] = $from->username;

            // Notify all clients that a new user has joined
            foreach ($this->clients as $client) {
                if ($client !== $from) {
                    $client->send(sprintf('User %s has joined the chat room', $from->username));
                }
            }

            // Send a welcome message to the user
            $from->send(sprintf('Welcome, %s!', $from->username));
			
			echo sprintf('Welcome, %s!' . "\n", $from->username);
        } else {
            // Broadcast the message to all clients except the sender
            foreach ($this->clients as $client) {
                if ($client !== $from) {
                    $client->send(sprintf('%s says: %s', $from->username, $msg));
                } else {
                    // Send a confirmation message to the sender
                    $client->send(sprintf('You say: %s', $msg));
                }
            }
			
			echo sprintf('%s says: %s' . "\n", $from->username, $msg);
        }

    }

    public function onClose(ConnectionInterface $conn)
    {
        // Remove the connection from the list of clients
        $this->clients->detach($conn);

        // Notify all clients that the user has left the chat room
        foreach ($this->clients as $client) {
            if ($client !== $conn) {
                $client->send(sprintf('User %s has left the chat room', $this->usernames[$conn->userId]));
            }
        }

        echo sprintf('Connection %d has disconnected' . "\n", $conn->userId);
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo sprintf('An error has occurred: %s' . "\n", $e->getMessage());

        $conn->close();
    }
}

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new ChatRoom()
        )
    ),
    8080
);


echo "Listening on port 8080...\n";

// Start the server
$server->run();