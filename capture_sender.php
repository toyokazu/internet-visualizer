<?php
require_once __DIR__ . '/phpws/phpws/websocket.client.php';
require __DIR__ . '/GeoIP2-php/vendor/autoload.php';
use \GeoIp2\Database\Reader;

// This creates the Reader object, which should be reused across
// lookups.


$options = getopt('c:f:g:hH:i:p:r:t:');
$capture_interval = 1;
$filter = '(http or ssl) and !ipv6 and !(ip.addr==224.0.0.0/4)';
$global_uri = 'https://agile.cse.kyoto-su.ac.jp/remote_addr.php';
$host = '127.0.0.1';
$interface = 'en0';
$port = '12345';
$repeat = 10;
$tshark = '/Applications/Wireshark.app/Contents/Resources/bin/tshark';
if (is_array($options)) {
  if (isset($options['c'])) {
    $capture_interval = (int)$options['c'];
  }
  if (isset($options['f'])) {
    $filter = $options['f'];
  }
  if (isset($options['g'])) {
    $global_uri = $options['g'];
  }
  if (isset($options['h'])) {
    print "usage: capture-sender.php [options]\n";
    print "options:\n";
    print " [-c <capture interval>]\n";
    print " [-f <filter>]\n";
    print " [-g <global addr conversion uri>]\n";
    print " [-h] show this message\n";
    print " [-H <websocket server host>]\n";
    print " [-i <interface>]\n";
    print " [-p <port>]\n";
    print " [-r <number of times to repeat>]\n";
    print " [-t <full path to tshark>]\n";
    exit(0);
  }
  if (isset($options['H'])) {
    $host = $options['H'];
  }
  if (isset($options['i'])) {
    $interface = $options['i'];
  }
  if (isset($options['p'])) {
    $port = (int)$options['p'];
  }
  if (isset($options['r'])) {
    $repeat = (int)$options['r'];
  }
  if (isset($options['t'])) {
    $tshark = $options['t'];
  }
}
// tshark コマンド，フィルタ等はここで指定
$tshark_cmd = $tshark . ' -i ' . $interface . " -t e -l -2 -Tfields -e col.No. -e col.Time -e col.Source -e col.Destination -e col.Protocol -e col.Length -e col.Info -R '" . $filter . "' 2>/dev/null";
//$mac_tshark_cmd = "/Applications/Wireshark.app/Contents/Resources/bin/tshark -i en3 -t e -l -2 -R 'http or ssl' 2>/dev/null";
//$mac_tshark_cmd = "/Applications/Wireshark.app/Contents/Resources/bin/tshark -i en0 -t e -l -2 -R 'http or ssl' 2>/dev/null";

// WebSocket サーバの指定
$websocket_uri = 'ws://' . $host . ':' . $port . '/';


// Replace "city" with the appropriate method for your database, e.g.,
// "country".
//$record = $reader->city('133.101.56.1');

//print($record->country->isoCode . "\n"); // 'US'
//print($record->country->name . "\n"); // 'United States'
//print($record->country->names['zh-CN'] . "\n"); // '美国'

//print($record->mostSpecificSubdivision->name . "\n"); // 'Minnesota'
//print($record->mostSpecificSubdivision->isoCode . "\n"); // 'MN'

//print($record->city->name . "\n"); // 'Minneapolis'

//print($record->postal->code . "\n"); // '55455'

//print($record->location->latitude . "\n"); // 44.9733
//print($record->location->longitude . "\n"); // -93.2323


/**
 * capture-sender.php
 * Send any incoming messages to all connected clients (except sender)
 */
class CaptureSender {
  protected $handle;
  protected $ws;
  protected $reader;
  protected $global_ip;

  public function __construct($capture_cmd, $ws_server, $global_uri) {
    $this->handle = popen($capture_cmd, 'r');
    $this->ws = new WebSocket($ws_server);
    $this->ws->open();
    $this->reader = new Reader(__DIR__ . '/GeoIP2-databases/GeoLite2-City.mmdb');
    // グローバルアドレスの取得
    $this->global_ip = file_get_contents($global_uri);
  }

  public function capture($interval, $repeat = 0) {
    $counter = $repeat;
    $capture_results = array();
    while ($counter > 0 || $repeat == 0) {
      $start_time = time();
      print "counter: " . $counter . "\n";
      while ((float)(time() - $start_time) < $interval) {
        //print "passed: " . (time() - $start_time) . "\n";
        //print "interval: " . $interval . "\n";
        //stream_set_timeout($this->handle, 1);
        stream_set_blocking($this->handle, 0);
        // read packet from tshark, parse it and pack into an array
        $buffer = fgets($this->handle);
        if (!empty($buffer)) {
          $raw_message = preg_split("/\s+/", $buffer, 7);
          $src_ip = $raw_message[2];
          $dst_ip = $raw_message[3];
          $ip_array = array($src_ip, $dst_ip);
          // IPv6 and multicast addresses are not supported yet
          // check by php is heavy. recommend to filter them out by tshak.
          //if (!$this->is_true($ip_array, 'all_ipv6')
          //!$this->is_treu($ip_array, 'all_multicast')) {
          $ip_array = $this->conv_addrs($ip_array, 'global_addr');
          $location_array = $this->conv_addrs($ip_array, 'location');
          $message = array(
            'number' => $raw_message[0],
            'time' =>  $raw_message[1],
            'src_ip' => $ip_array[0],
            'src_location' => $location_array[0],
            'dst_ip' => $ip_array[1],
            'dst_location' => $location_array[1],
            'protocol' => $raw_message[4],
            'length' => $raw_message[5],
            'data' => $raw_message[6]
          );
          print_r($message);
          array_push($capture_results, $message);
          //}
        }
      }
      if (!empty($capture_results)) {
        $websocket_msg = WebSocketMessage::create(json_encode(array('type' => 'packets', 'packets' => $capture_results)));
        $this->ws->sendMessage($websocket_msg);
      }
      $capture_results = array();
      $counter -= 1;
    }
  }

  public function close() {
    fclose($this->handle);
    $this->ws->close();
  }

  function is_private($ip) 
  {
    $ip = ip2long($ip);
    $net_a = ip2long('10.255.255.255') >> 24; 
    $net_b = ip2long('172.31.255.255') >> 20; 
    $net_c = ip2long('192.168.255.255') >> 16; 

    return $ip >> 24 === $net_a || $ip >> 20 === $net_b || $ip >> 16 === $net_c; 
  }

  function is_multicast($ip)
  {
    $ip = ip2long($ip);
    $net_a = ip2long('224.0.0.0') >> 28;

    return $ip >> 28 === $net_a;
  }

  function is_ipv6($ip)
  {
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
  }

  function all_ipv6($bool, $ip)
  {
    return ($bool && $this->is_ipv6($ip));
  }

  function is_true(array $ip_array, $check_func)
  {
    return array_reduce($ip_array, array($this, $check_func), TRUE);
  }

  function global_addr($ip)
  {
    if ($this->is_private($ip)) {
      return $this->global_ip;
    } else {
      return $ip;
    }
  }

  function location($ip)
  {
    $record = $this->reader->city($ip);
    return array(
      'isoCode' => $record->country->isoCode, // 'US'
      'cityName' => $record->city->name, // 'Minneapolis'
      'lat' => $record->location->latitude, // 44.9733
      'long' => $record->location->longitude // -93.2323
    );
  }

  function conv_addrs(array $ip_array, $conv_func)
  {
    return array_map(array($this, $conv_func), $ip_array);
  }
}
$sender = new CaptureSender($tshark_cmd, $websocket_uri, $global_uri);
$sender->capture($capture_interval, $repeat);
$sender->close();
?>
