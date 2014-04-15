# Internet Visualizer

*This is a obsolete version. Please use* https://github.com/toyokazu/internet-visualizer2

*Internet Visualizer* is a real time network traffic visualization tool using WebSocket and D3.js. It aims to learn how your PC is accessing to the Internet. The Internet technology is very complicated and it is difficult to grasp all of the mechanism for beginners. We tried to help understanding the mechanism by abstracting it from the various viewpoints.

# How to use

## clone the source code

    % git clone https://github.com/toyokazu/internet-visualizer.git
    % cd internet-visualizer

## install Ratchet, phpws, GeoIP2 and Wireshark

Please install Ratchet (https://github.com/cboden/Ratchet) and phpws (https://github.com/Devristo/phpws) into the cloned directory for backend services (stream-server.php and capture-sender.php). And please also install GeoLite2 PHP library and GeoLite2 City database into cloned directory as GeoIP2-php/* and GeoIP2-databases/GeoLite2-City.mmdb. GeoLite2 related tools can be downloaded from http://dev.maxmind.com/geoip/geoip2/geolite2/.

capture_sender.php requires tshark command which is a packet capture software included in Wireshark. Thus you must install Wireshark (http://www.wireshark.org/download.html). capture_sender.php specifies a path of Wireshark for MacOS X as a default. You can specify the different path by -t option.

## boot websocket server

Websocket server can be started by the following command:

    % php stream-server.php

The start-up options are shown with option '-h'.

## setup global address provider

Please put remote_addr.php to a web server running with global IP address. You can use your own server instead of the default server.

## start capture

You can start caputuring of a network interface (e.g. en0) by the following command:

    % php capture_sender.php -i en0

The start-up options are shown with option '-h'.

## visualize

You can see visualized results by accessing visualizer/index.html by web browser.

