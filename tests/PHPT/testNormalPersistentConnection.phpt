--TEST--
Tests whether connections can be made.
--CGI--
--FILE--
<?php
namespace PEAR2\Net\Transmitter;

$client1 = new TcpClient(
    REMOTE_HOSTNAME, REMOTE_PORT, true
);
$client2 = new TcpClient(
    REMOTE_HOSTNAME, REMOTE_PORT, true
);
echo $client1->isFresh();
echo $client2->isFresh();
echo $client1->receive(1);
echo (int) $client1->isFresh();
echo (int) $client2->isFresh();
$client1->close();
?>
--EXPECT--
11t00