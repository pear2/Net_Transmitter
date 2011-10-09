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
     * @var SocketServerConnectionTransmitter
     */
    protected $conn;
    
    public static function setUpBeforeClass()
    {
        self::$server = stream_socket_server(
            'tcp://' . LOCAL_HOSTNAME . ':' . LOCAL_PORT,
            self::$errorno, self::$errstr
        );
    }
    
    public static function tearDownAfterClass()
    {
        fclose(self::$server);
    }
    
    public function setUp()
    {
        $this->conn = new SocketServerConnectionTransmitter(
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
            1, $this->conn->sendStream($stream),
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
            $size, $this->conn->sendStream($stream),
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
}