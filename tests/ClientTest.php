<?php
namespace PEAR2\Net\Transmitter;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    
    /**
     * @var TcpClient
     */
    protected $client;
    
    public function setUp()
    {
        $this->client = new TcpClient(REMOTE_HOSTNAME, REMOTE_PORT);
    }
    
    public function tearDown()
    {
        $this->client->close();
        unset($this->client);
    }
    
    public function testOneByteEcho()
    {
        $byte = '1';
        $this->client->send($byte);
        $this->assertEquals(
            $byte, $this->client->receive(1), 'Wrong byte echoed.'
        );
    }
    
    public function test3MegaBytesEcho()
    {
        $size = 3/*m*/ * 1024/*k*/ * 1024/*b*/;
        $contents = str_repeat('2', $size);
        $this->client->send($contents);
        $this->assertEquals(
            $contents, $this->client->receive($size), 'Wrong contents echoed.'
        );
    }
    
    public function testOneByteEchoStreamSend()
    {
        $stream = fopen('php://temp', 'r+b');
        fwrite($stream, '3');
        rewind($stream);
        $this->client->sendStream($stream);
        $this->assertEquals(
            stream_get_contents($stream), $this->client->receive(1),
            'Wrong byte echoed.'
        );
    }
    
    public function test3MegaBytesEchoStreamSend()
    {
        $size = 3/*m*/ * 1024/*k*/ * 1024/*b*/;
        $stream = fopen('php://temp', 'r+b');
        fwrite($stream, str_repeat('4', $size));
        rewind($stream);
        $this->client->sendStream($stream);
        $this->assertEquals(
            stream_get_contents($stream), $this->client->receive($size),
            'Wrong contents echoed.'
        );
    }
    
    public function testOneByteEchoStreamReceive()
    {
        $byte = '5';
        $this->client->send($byte);
        $this->assertEquals(
            $byte, stream_get_contents($this->client->receiveStream(1)),
            'Wrong byte echoed.'
        );
    }
    
    public function test3MegaBytesEchoStreamReceive()
    {
        $size = 3/*m*/ * 1024/*k*/ * 1024/*b*/;
        $contents = str_repeat('6', $size);
        $this->client->send($contents);
        $this->assertEquals(
            $contents, stream_get_contents($this->client->receiveStream($size)),
            'Wrong contents echoed.'
        );
    }
    
    public function testClientReceivingFilterCollection()
    {
        $filters = new FilterCollection();
        $filters->append('string.toupper');
        $this->assertEquals(
            'T',
            stream_get_contents($this->client->receiveStream(1, $filters)),
            'Wrong contents echoed.'
        );
    }
    
    public function testPersistentClientConnection()
    {
        $this->client = new TcpClient(
            REMOTE_HOSTNAME, REMOTE_PORT, true
        );
        $client = new TcpClient(
            REMOTE_HOSTNAME, REMOTE_PORT, true
        );
        $this->assertTrue($this->client->isFresh());
        $this->assertTrue($client->isFresh());
        $this->assertEquals('t', $this->client->receive(1));
        $this->assertFalse($this->client->isFresh());
        $this->assertFalse($client->isFresh());
        $client->close();
    }
    
    public function testClientReceivingIncompleteData()
    {
        try {
            $this->client->receive(2);
            $this->fail('Receiving had to fail.');
        } catch(SocketException $e) {
            $this->assertEquals(4, $e->getCode(), 'Improper exception code.');
        }
    }
    
    public function testClientReceivingIncompleteDataStream()
    {
        try {
            $this->client->receiveStream(2);
            $this->fail('Receiving had to fail.');
        } catch(SocketException $e) {
            $this->assertEquals(5, $e->getCode(), 'Improper exception code.');
        }
    }
    
    public function testServerReceivingIncompleteData()
    {
        $this->assertEquals(1, $this->client->send('t'), 'Wrong amount sent.');
    }
    
    public function testServerReceivingIncompleteDataStream()
    {
        $this->assertEquals(1, $this->client->send('t'), 'Wrong amount sent.');
    }
    
    public function testClientSendingIncompleteData()
    {
        $size = 3/*m*/ * 1024/*k*/ * 1024/*b*/;
        $contents = str_repeat('7', $size);
        try {
            $this->client->send($contents);
        } catch(SocketException $e) {
            $this->assertEquals(2, $e->getCode(), 'Improper exception code.');
        }
    }
    
    public function testClientSendingIncompleteDataStream()
    {
        $size = 3/*m*/ * 1024/*k*/ * 1024/*b*/;
        $stream = fopen('php://temp', 'r+b');
        fwrite($stream, str_repeat('8', $size));
        rewind($stream);
        try {
            $this->client->sendStream($stream);
        } catch(SocketException $e) {
            $this->assertEquals(3, $e->getCode(), 'Improper exception code.');
        }
    }
}