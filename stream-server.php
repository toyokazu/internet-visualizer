<?php
require __DIR__ . '/Ratchet/vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;


/**
 * stream-server.php
 * Send any incoming messages to all connected clients (except sender)
 */
class StreamServer implements MessageComponentInterface {
  //class Chat implements MessageComponentInterface {
  protected $clients;
  protected $debug;

  public function __construct($debug = false) {
    $this->clients = new \SplObjectStorage;
    $this->debug = $debug;
  }

  public function onOpen(ConnectionInterface $conn) {
    $this->clients->attach($conn);
  }

  public function onMessage(ConnectionInterface $from, $msg) {
    foreach ($this->clients as $client) {
      if ($this->debug) {
        print "recieve: " . $msg . "\n";
      }
      if ($from != $client) {
        $client->send($msg);
      }
    }
  }

  public function onClose(ConnectionInterface $conn) {
    $this->clients->detach($conn);
  }

  public function onError(ConnectionInterface $conn, \Exception $e) {
    $conn->close();
  }
}

$options = getopt('dhH:p:');
$debug = false;
$host = '0.0.0.0';
$port = 12345;
if (is_array($options)) {
  if (isset($options['h'])) {
    print "usage: stream-server.php [options]\n";
    print "options:\n";
    print " [-d] debug mode\n";
    print " [-h] show this message\n";
    print " [-H <ip address to listen>]\n";
    print " [-p <port>]\n";
    exit(0);
  }
  if (isset($options['d'])) {
    $debug = true;
  }
  if (isset($options['H'])) {
    $host = $options['H'];
  }
  if (isset($options['p'])) {
    $port = (int)$options['p'];
  }
}
// Run the server application through the WebSocket protocol on port 8080
$server = IoServer::factory(new WsServer(new StreamServer($debug)), $port, $host);
$server->run();

?>
