<?php
namespace PEAR2\Net\Transmitter;

class ServerTest extends \PHPUnit_Framework_TestCase
{
    
    /**
     * @var resource
     */
    protected static $server;
    
    /**
     * @var int
     */
    protected static $errorno;
    
    /**
     * @var string
     */
    protected static $errstr;
    
    /**
     * @var TcpServerConnection
     */
    protected $conn;
    
    public static function setUpBeforeClass()
    {
        $hostname = strpos(LOCAL_HOSTNAME, ':') !== false
            ? '[' . LOCAL_HOSTNAME . ']' : LOCAL_HOSTNAME;
        self::$server = stream_socket_server(
            "tcp://{$hostname}:" . LOCAL_PORT,
            self::$errorno, self::$errstr
        );
    }
    
    public static function tearDownAfterClass()
    {
        fclose(self::$server);
    }
    
    public function setUp()
    {
        $this->conn = new TcpServerConnection(
            self::$server, 1/*h*/ * 60/*m*/ * 60/*s*/
        );
        $this->assertEquals(REMOTE_HOSTNAME, $this->conn->getPeerIP());
        $this->assertInternalType('int', $this->conn->getPeerPort());
    }
    
    public function tearDown()
    {
        unset($this->conn);
    }
    
    public function testOneByteEcho()
    {
        $this->assertEquals(
            1, $this->conn->send($this->conn->receive(1)),
            'Wrong amount echoed.'
        );
    }
    
    public function test3MegaBytesEcho()
    {
        $size = 3/*m*/ * 1024/*k*/ * 1024/*b*/;
        $this->assertEquals(
            $size, $this->conn->send($this->conn->receive($size)),
            'Wrong amount echoed.'
        );
    }
    
    public function testOneByteEchoStreamSend()
    {
        $stream = fopen('php://temp', 'r+b');
        fwrite($stream, $this->conn->receive(1));
        rewind($stream);
        $this->assertEquals(
            1, $this->conn->send($stream),
            'Wrong amount echoed.'
        );
    }
    
    public function test3MegaBytesEchoStreamSend()
    {
        $size = 3/*m*/ * 1024/*k*/ * 1024/*b*/;
        $stream = fopen('php://temp', 'r+b');
        fwrite($stream, $this->conn->receive($size));
        rewind($stream);
        $this->assertEquals(
            $size, $this->conn->send($stream),
            'Wrong amount echoed.'
        );
    }
    
    public function testOneByteEchoStreamReceive()
    {
        $this->assertEquals(
            1,
            $this->conn->send(
                stream_get_contents($this->conn->receiveStream(1))
            ),
            'Wrong amount echoed.'
        );
    }
    
    public function test3MegaBytesEchoStreamReceive()
    {
        $size = 3/*m*/ * 1024/*k*/ * 1024/*b*/;
        $this->assertEquals(
            $size,
            $this->conn->send(
                stream_get_contents($this->conn->receiveStream($size))
            ),
            'Wrong amount echoed.'
        );
    }
    
    public function testClientReceivingFilterCollection()
    {
        $this->assertEquals(1, $this->conn->send('t'), 'Wrong amount sent.');
    }
    
    public function testPersistentClientConnectionRESET()
    {
        $this->assertTrue(true);
    }
    
    public function testPersistentClientConnection()
    {
        $this->assertEquals(1, $this->conn->send('t'), 'Wrong amount sent.');
    }
    
    public function testClientReceivingIncompleteData()
    {
        $this->assertEquals(1, $this->conn->send('t'), 'Wrong amount sent.');
    }
    
    public function testClientReceivingIncompleteDataStream()
    {
        $this->assertEquals(1, $this->conn->send('t'), 'Wrong amount sent.');
    }
    
    public function testServerReceivingIncompleteData()
    {
        try {
            $this->conn->receive(2);
            $this->fail('Receiving had to fail.');
        } catch(SocketException $e) {
            $this->assertEquals(4, $e->getCode(), 'Improper exception code.');
        }
    }
    
    public function testServerReceivingIncompleteDataStream()
    {
        try {
            $this->conn->receiveStream(2);
            $this->fail('Receiving had to fail.');
        } catch(SocketException $e) {
            $this->assertEquals(5, $e->getCode(), 'Improper exception code.');
        }
    }
    
    public function testClientSendingIncompleteData()
    {
        $this->assertEquals('777', $this->conn->receive(3));
        $this->conn->close();
    }
    
    public function testClientSendingIncompleteDataStream()
    {
        $this->assertEquals('888', $this->conn->receive(3));
        $this->conn->close();
    }
    
    public function testClientTimingOut()
    {
        $this->conn->send('999');
        sleep(1);
        $this->conn->send('999');
        sleep(3);
        $this->conn->send('999');
    }
    
    public function testClientTimingOutStream()
    {
        $this->conn->send('aaa');
        sleep(1);
        $this->conn->send('aaa');
        sleep(3);
        $this->conn->send('aaa');
    }
    
    
    public function testSetBuffer()
    {
        $this->assertFalse($this->conn->setBuffer(0, 'unknown direction'));
        $this->assertFalse($this->conn->setBuffer(-1));
        $this->assertTrue(
            $this->conn->setBuffer(99, Stream::DIRECTION_RECEIVE)
        );
    }
    
    public function testSetChunk()
    {
        $defaultChunks = $this->conn->getChunk();
        $this->assertInternalType('array', $defaultChunks);
        
        $this->assertFalse($this->conn->getChunk('unknown direction'));
        $this->assertFalse($this->conn->setChunk(1, 'unknown direction'));
        
        $this->assertFalse($this->conn->setChunk(0));
        $this->assertFalse($this->conn->setChunk(0, Stream::DIRECTION_ALL));
        $this->assertFalse($this->conn->setChunk(0, Stream::DIRECTION_SEND));
        $this->assertFalse(
            $this->conn->setChunk(0, Stream::DIRECTION_RECEIVE)
        );
        
        $this->assertTrue(
            $this->conn->setChunk(1, Stream::DIRECTION_RECEIVE)
        );
        $this->assertEquals(
            1, $this->conn->getChunk(Stream::DIRECTION_RECEIVE)
        );
        $this->assertEquals(
            $defaultChunks[Stream::DIRECTION_SEND],
            $this->conn->getChunk(Stream::DIRECTION_SEND)
        );
        $this->assertEquals(
            array(
                Stream::DIRECTION_RECEIVE => 1,
                Stream::DIRECTION_SEND => $defaultChunks[Stream::DIRECTION_SEND]
            ),
            $this->conn->getChunk()
        );
        
        $this->assertTrue(
            $this->conn->setChunk(1, Stream::DIRECTION_SEND)
        );
        $this->assertEquals(
            1, $this->conn->getChunk(Stream::DIRECTION_SEND)
        );
        $this->assertEquals(
            1, $this->conn->getChunk(Stream::DIRECTION_RECEIVE)
        );
        $this->assertEquals(
            array(Stream::DIRECTION_RECEIVE => 1,Stream::DIRECTION_SEND => 1),
            $this->conn->getChunk()
        );
        
        $this->assertTrue(
            $this->conn->setChunk(2)
        );
        $this->assertEquals(
            2, $this->conn->getChunk(Stream::DIRECTION_SEND)
        );
        $this->assertEquals(
            2, $this->conn->getChunk(Stream::DIRECTION_RECEIVE)
        );
        $this->assertEquals(
            array(Stream::DIRECTION_RECEIVE => 2,Stream::DIRECTION_SEND => 2),
            $this->conn->getChunk()
        );
    }
    
    public function testShutdown()
    {
        $this->assertEquals('bbb', $this->conn->receive(3));
        $this->conn->send('bbb');
        $this->assertFalse($this->conn->shutdown('undefined direction'));
        $this->assertTrue($this->conn->shutdown(Stream::DIRECTION_RECEIVE));
        try {
            $this->conn->receive(1);
            $this->fail('Receiving had to fail.');
        } catch(SocketException $e) {
            $this->assertEquals(4, $e->getCode(), 'Improper exception code.');
        }
    }
}