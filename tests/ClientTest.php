<?php
namespace PEAR2\Net\Transmitter;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    
    /**
     * @var SocketClientTransmitter
     */
    protected $client;
    
    public function setUp()
    {
        $this->client = new SocketClientTransmitter(
            REMOTE_HOSTNAME, REMOTE_PORT
        );
    }
    
    public function tearDown()
    {
        unset($this->client);
    }
    
    public function testOneByteEcho()
    {
        $byte = 't';
        $this->client->send($byte);
        $this->assertEquals(
            $byte, $this->client->receive(1), 'Wrong byte echoed.'
        );
    }
}