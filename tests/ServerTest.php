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
        static::$server = stream_socket_server(
            "tcp://{$hostname}:" . LOCAL_PORT,
            static::$errorno,
            static::$errstr
        );
    }

    public static function tearDownAfterClass()
    {
        fclose(static::$server);
    }

    public function setUp()
    {
        $this->conn = new TcpServerConnection(
            static::$server,
            //(1 * 60 * 60)/*h*/ //+
            (3 * 60)/*m*/ //+
            //1/*s*/
        );
        $this->assertSame(REMOTE_HOSTNAME, $this->conn->getPeerIP());
        $this->assertInternalType('int', $this->conn->getPeerPort());
    }

    public function tearDown()
    {
        unset($this->conn);
    }

    public function testOneByteEcho()
    {
        $this->assertSame(
            1,
            $this->conn->send($this->conn->receive(1)),
            'Wrong amount echoed.'
        );
    }

    public function testOneByteDelayedEcho()
    {
        $byte = $this->conn->receive(1);
        sleep(ini_get('default_socket_timeout') + 2);
        $this->assertSame(
            1,
            $this->conn->send($byte),
            'Wrong amount echoed.'
        );
    }

    /**
     * N/A
     * 
     * @return void
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function testOneByteDelayedEchoFail()
    {
        $byte = $this->conn->receive(1);
        sleep(ini_get('default_socket_timeout') + 3);
        $this->assertFalse($this->conn->isAvailable());
    }

    public function test3MegaBytesEcho()
    {
        $size = 3/*m*/ * 1024/*k*/ * 1024/*b*/;

        $this->assertSame(
            $size,
            $this->conn->send($this->conn->receive($size)),
            'Wrong amount echoed.'
        );
    }

    public function test3MegaBytesDelayedEcho()
    {
        $size = 3/*m*/ * 1024/*k*/ * 1024/*b*/;

        $data = $this->conn->receive($size);
        sleep(ini_get('default_socket_timeout') + 1);
        $this->assertSame(
            $size,
            $this->conn->send($data),
            'Wrong amount echoed.'
        );
    }

    public function test3MegaBytesLongDelayedEcho()
    {
        $size = 3/*m*/ * 1024/*k*/ * 1024/*b*/;

        $data = $this->conn->receive($size);
        sleep(ini_get('default_socket_timeout') + 5);
        $this->assertSame(
            $size,
            $this->conn->send($data),
            'Wrong amount echoed.'
        );
    }

    /*
    public function testOneByteDelayedEchoSend()
    {
        $this->markTestIncomplete('The server never gives up accepting data.');
        $this->conn->setBuffer(0);
        //echo date('H:s;');
        sleep(ini_get('default_socket_timeout') + 2);
        //echo date('H:s;');
        $byte = $this->conn->receive(1);
        //echo date('H:s;');
        $this->assertSame(
            1,
            $this->conn->send($byte),
            'Wrong amount echoed.'
        );
        //echo date('H:s;');
    }
    */

    public function test3MegaBytesLongDelayedEchoSend()
    {
        $size = 3/*m*/ * 1024/*k*/ * 1024/*b*/;

        sleep(ini_get('default_socket_timeout') + 5);
        $data = $this->conn->receive($size);
        $this->assertSame(
            $size,
            $this->conn->send($data),
            'Wrong amount echoed.'
        );
    }

    public function testOneByteEchoStreamSend()
    {
        $stream = fopen('php://temp', 'r+b');
        fwrite($stream, $this->conn->receive(1));
        rewind($stream);
        $this->assertSame(
            1,
            $this->conn->send($stream),
            'Wrong amount echoed.'
        );
    }

    public function test3MegaBytesEchoStreamSend()
    {
        $size = 3/*m*/ * 1024/*k*/ * 1024/*b*/;
        $stream = fopen('php://temp', 'r+b');
        fwrite($stream, $this->conn->receive($size));
        rewind($stream);
        $this->assertSame(
            $size,
            $this->conn->send($stream),
            'Wrong amount echoed.'
        );
    }

    public function testOneByteEchoStreamReceive()
    {
        $this->assertSame(
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
        $this->assertSame(
            $size,
            $this->conn->send(
                stream_get_contents($this->conn->receiveStream($size))
            ),
            'Wrong amount echoed.'
        );
    }

    public function testOffsetSend()
    {
        $this->assertSame('bcd', $this->conn->receive(3));
        $this->assertSame('bcd', $this->conn->receive(3));
        $this->assertSame('d', $this->conn->receive(1));
        $this->assertSame('cd', $this->conn->receive(2));
    }

    public function testLengthSend()
    {
        $this->assertSame('a', $this->conn->receive(1));
        $this->assertSame('a', $this->conn->receive(1));
        $this->assertSame('c', $this->conn->receive(1));
        $this->assertSame('bc', $this->conn->receive(2));
    }

    public function testClientReceivingFilterCollection()
    {
        $this->assertSame(1, $this->conn->send('t'), 'Wrong amount sent.');
    }

    public function testPersistentClientConnectionRESET()
    {
        $this->assertTrue(true);
    }

    public function testPersistentClientConnection()
    {
        $this->assertSame(1, $this->conn->send('t'), 'Wrong amount sent.');
    }

    public function testClientReceivingIncompleteData()
    {
        $this->assertSame(1, $this->conn->send('t'), 'Wrong amount sent.');
    }

    public function testClientReceivingIncompleteDataStream()
    {
        $this->assertSame(1, $this->conn->send('t'), 'Wrong amount sent.');
    }

    public function testServerReceivingIncompleteData()
    {
        try {
            $this->conn->receive(2);
            $this->fail('Receiving had to fail.');
        } catch (SocketException $e) {
            $this->assertSame('t', $e->getFragment());
            $this->assertSame(4, $e->getCode(), 'Improper exception code.');
        }
    }

    public function testServerReceivingIncompleteDataStream()
    {
        try {
            $this->conn->receiveStream(2);
            $this->fail('Receiving had to fail.');
        } catch (SocketException $e) {
            $this->assertSame('t', stream_get_contents($e->getFragment()));
            $this->assertSame(5, $e->getCode(), 'Improper exception code.');
        }
    }

    public function testClientSendingIncompleteData()
    {
        $this->assertSame('777', $this->conn->receive(3));
        $this->conn->close();
    }

    public function testClientSendingIncompleteDataStream()
    {
        $this->assertSame('888', $this->conn->receive(3));
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
        $this->assertSame(
            1,
            $this->conn->getChunk(Stream::DIRECTION_RECEIVE)
        );
        $this->assertSame(
            $defaultChunks[Stream::DIRECTION_SEND],
            $this->conn->getChunk(Stream::DIRECTION_SEND)
        );
        $this->assertSame(
            array(
                Stream::DIRECTION_SEND
                    => $defaultChunks[Stream::DIRECTION_SEND],
                Stream::DIRECTION_RECEIVE
                    => 1
            ),
            $this->conn->getChunk()
        );
        
        $this->assertTrue(
            $this->conn->setChunk(1, Stream::DIRECTION_SEND)
        );
        $this->assertSame(
            1,
            $this->conn->getChunk(Stream::DIRECTION_SEND)
        );
        $this->assertSame(
            1,
            $this->conn->getChunk(Stream::DIRECTION_RECEIVE)
        );
        $this->assertSame(
            array(
                Stream::DIRECTION_SEND => 1,
                Stream::DIRECTION_RECEIVE => 1
            ),
            $this->conn->getChunk()
        );
        
        $this->assertTrue(
            $this->conn->setChunk(2)
        );
        $this->assertSame(
            2,
            $this->conn->getChunk(Stream::DIRECTION_SEND)
        );
        $this->assertSame(
            2,
            $this->conn->getChunk(Stream::DIRECTION_RECEIVE)
        );
        $this->assertSame(
            array(
                Stream::DIRECTION_SEND => 2,
                Stream::DIRECTION_RECEIVE => 2
            ),
            $this->conn->getChunk()
        );
    }

    public function testShutdown()
    {
        $this->assertSame('bbb', $this->conn->receive(3));
        $this->conn->send('bbb');
        $this->assertFalse($this->conn->shutdown('undefined direction'));
        $this->assertTrue($this->conn->shutdown(Stream::DIRECTION_RECEIVE));
        try {
            $this->conn->receive(1);
            $this->fail('Receiving had to fail.');
        } catch (SocketException $e) {
            $this->assertSame(4, $e->getCode(), 'Improper exception code.');
        }
    }
}
