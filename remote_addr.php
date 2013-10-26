<?php
// Plese locate this script to a server with global IP address.
// The URI can be used to convert from a private address to
// a global address.
print htmlspecialchars($_SERVER['REMOTE_ADDR'],ENT_QUOTES);
?>
