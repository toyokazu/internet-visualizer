# Internet Visualizer

*Internet Visualizer* is a real time network traffic visualization tool using WebSocket and D3.js. It aims to learn how your PC is accessing to the Internet. The Internet technology is very complicated and it is difficult to grasp all of the mechanism for beginners. We tried to help understanding the mechanism by abstracting it from the various viewpoints.

# How to use

## clone the source code

    % git clone https://github.com/toyokazu/internet-visualizer.git
    % cd internet-visualizer

## install Ratchet, phpws and GeoIP2

Please install Ratchet (http://maker.github.io/ratchet/) and phpws (https://github.com/Devristo/phpws) into the cloned directory for backend services (stream-server.php and capture-sender.php). And please also install GeoLite2 PHP library and GeoLite2 City database into cloned directory as GeoIP2-php/* and GeoIP2-databases/GeoLite2-City.mmdb. GeoLite2 related tools can be downloaded from http://dev.maxmind.com/geoip/geoip2/geolite2/.

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

