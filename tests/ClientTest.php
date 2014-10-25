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
        $this->assertInstanceOf(__NAMESPACE__ . '\TcpClient', $this->client);
        if ($this->client->isPersistent()) {
            $this->client->close();
        }
        unset($this->client);
    }

    public function testOneByteEcho()
    {
        $byte = '1';

        $this->client->send($byte);
        $this->assertSame(
            $byte,
            $this->client->receive(1),
            'Wrong byte echoed.'
        );
    }

    public function testOneByteDelayedEcho()
    {
        $byte = '1';
        $timeout = ini_get('default_socket_timeout') + 3;

        $this->client->send($byte);
        if ($this->client->isDataAwaiting($timeout)) {
            $this->assertSame(
                $byte,
                $this->client->receive(1),
                'Wrong byte echoed.'
            );
        }
    }

    public function testOneByteDelayedEchoFail()
    {
        $byte = '1';
        $timeout = ini_get('default_socket_timeout') + 1;

        $this->client->send($byte);
        $this->assertFalse($this->client->isDataAwaiting($timeout));
    }

    public function test3MegaBytesEcho()
    {
        $size = 3/*m*/ * 1024/*k*/ * 1024/*b*/;
        $contents = str_repeat('2', $size);

        $this->client->send($contents);
        $this->assertSame(
            $contents,
            $this->client->receive($size),
            'Wrong contents echoed.'
        );
    }

    public function test3MegaBytesDelayedEcho()
    {
        $size = 3/*m*/ * 1024/*k*/ * 1024/*b*/;
        $contents = str_repeat('2', $size);
        $timeout = ini_get('default_socket_timeout') + 3;

        $this->client->send($contents);
        if ($this->client->isDataAwaiting($timeout)) {
            $this->assertSame(
                $contents,
                $this->client->receive($size),
                'Wrong contents echoed.'
            );
        }
    }

    public function test3MegaBytesLongDelayedEcho()
    {
        $size = 3/*m*/ * 1024/*k*/ * 1024/*b*/;
        $contents = str_repeat('2', $size);

        $this->client->send($contents);
        if ($this->client->isDataAwaiting(null)) {
            $this->assertSame(
                $contents,
                $this->client->receive($size),
                'Wrong contents echoed.'
            );
        }
    }

    /*
    public function testOneByteDelayedEchoSend()
    {
        $this->markTestIncomplete('The server never gives up accepting data.');
        $this->client->setBuffer(0);
        $byte = '1';
        $timeout = ini_get('default_socket_timeout') + 4;
        sleep(1);
        echo date('H:s;');
        if ($this->client->isAcceptingData($timeout)) {
            echo date('H:s;');
            $this->client->send($byte);
            //echo date('H:s;');
            $this->assertSame(
                $byte,
                $this->client->receive(1),
                'Wrong byte echoed.'
            );
            //echo date('H:s;');
        }
    }
    */

    public function test3MegaBytesLongDelayedEchoSend()
    {
        $size = 3/*m*/ * 1024/*k*/ * 1024/*b*/;
        $contents = str_repeat('2', $size);

        if ($this->client->isAcceptingData(null)) {
            $this->client->send($contents);
            $this->assertSame(
                $contents,
                $this->client->receive($size),
                'Wrong contents echoed.'
            );
        }
    }

    public function testOneByteEchoStreamSend()
    {
        $stream = fopen('php://temp', 'r+b');
        fwrite($stream, '3');
        rewind($stream);
        $this->client->send($stream);
        $this->assertSame(
            stream_get_contents($stream),
            $this->client->receive(1),
            'Wrong byte echoed.'
        );
    }

    public function test3MegaBytesEchoStreamSend()
    {
        $size = 3/*m*/ * 1024/*k*/ * 1024/*b*/;
        $stream = fopen('php://temp', 'r+b');
        fwrite($stream, str_repeat('4', $size));
        rewind($stream);
        $this->client->send($stream);
        $this->assertSame(
            stream_get_contents($stream),
            $this->client->receive($size),
            'Wrong contents echoed.'
        );
    }

    public function testOneByteEchoStreamReceive()
    {
        $byte = '5';
        $this->client->send($byte);
        $this->assertSame(
            $byte,
            stream_get_contents($this->client->receiveStream(1)),
            'Wrong byte echoed.'
        );
    }

    public function test3MegaBytesEchoStreamReceive()
    {
        $size = 3/*m*/ * 1024/*k*/ * 1024/*b*/;
        $contents = str_repeat('6', $size);
        $this->client->send($contents);
        $this->assertSame(
            $contents,
            stream_get_contents($this->client->receiveStream($size)),
            'Wrong contents echoed.'
        );
    }

    public function testOffsetSend()
    {
        $contents = 'abcd';
        $this->assertSame(3, $this->client->send($contents, 1));
        
        $stream = fopen('php://temp', 'r+b');
        fwrite($stream, $contents);
        rewind($stream);
        $this->assertSame(3, $this->client->send($stream, 1));
        fseek($stream, 3, SEEK_SET);
        $this->assertSame(1, $this->client->send($stream));
        $this->assertSame(2, $this->client->send($stream, 2));
    }

    public function testLengthSend()
    {
        $contents = 'abcd';
        $this->assertSame(1, $this->client->send($contents, null, 1));
        
        $stream = fopen('php://temp', 'r+b');
        fwrite($stream, $contents);
        rewind($stream);
        $this->assertSame(1, $this->client->send($stream, null, 1));
        fseek($stream, 2, SEEK_SET);
        $this->assertSame(1, $this->client->send($stream, null, 1));
        $this->assertSame(2, $this->client->send($stream, 1, 2));
    }

    public function testClientReceivingFilterCollection()
    {
        $filters = new FilterCollection();
        $filters->append('string.toupper');
        $this->assertSame(
            'T',
            stream_get_contents($this->client->receiveStream(1, $filters)),
            'Wrong contents echoed.'
        );
    }

    public function testPersistentClientConnection()
    {
        $this->client = new TcpClient(
            REMOTE_HOSTNAME,
            REMOTE_PORT,
            true
        );
        $client = new TcpClient(
            REMOTE_HOSTNAME,
            REMOTE_PORT,
            true
        );
        $this->assertTrue($this->client->isFresh());
        $this->assertTrue($client->isFresh());
        $this->assertTrue($this->client->isPersistent());
        $this->assertTrue($client->isPersistent());
        $this->assertSame('t', $this->client->receive(1));
        $this->assertFalse($this->client->isFresh());
        $this->assertFalse($client->isFresh());
        $client->close();
    }

    public function testClientReceivingIncompleteData()
    {
        try {
            $this->client->receive(2);
        } catch (SocketException $e) {
            $this->assertSame('t', $e->getFragment());
            $this->assertSame(4, $e->getCode(), 'Improper exception code.');
        }
    }

    public function testClientReceivingIncompleteDataStream()
    {
        try {
            $this->client->receiveStream(2);
            $this->fail('Receiving had to fail.');
        } catch (SocketException $e) {
            $this->assertSame('t', stream_get_contents($e->getFragment()));
            $this->assertSame(5, $e->getCode(), 'Improper exception code.');
        }
    }

    public function testServerReceivingIncompleteData()
    {
        $this->assertSame(1, $this->client->send('t'), 'Wrong amount sent.');
    }

    public function testServerReceivingIncompleteDataStream()
    {
        $this->assertSame(1, $this->client->send('t'), 'Wrong amount sent.');
    }

    public function testClientSendingIncompleteData()
    {
        $size = 3/*m*/ * 1024/*k*/ * 1024/*b*/;
        $contents = str_repeat('7', $size);
        try {
            $this->client->send($contents);
            $this->fail('Sending had to fail.');
        } catch (SocketException $e) {
            $this->assertLessThan(
                $size,
                $e->getFragment(),
                'Improper exception code.'
            );
            $this->assertSame(3, $e->getCode(), 'Improper exception code.');
        }
    }

    public function testClientSendingIncompleteDataStream()
    {
        $size = 3/*m*/ * 1024/*k*/ * 1024/*b*/;
        $stream = fopen('php://temp', 'r+b');
        fwrite($stream, str_repeat('8', $size));
        rewind($stream);
        try {
            $this->client->send($stream);
            $this->fail('Sending had to fail.');
        } catch (SocketException $e) {
            $this->assertLessThan(
                $size,
                $e->getFragment(),
                'Improper exception code.'
            );
            $this->assertSame(2, $e->getCode(), 'Improper exception code.');
        }
    }

    public function testClientTimingOut()
    {
        $this->assertSame('999', $this->client->receive(3));
        $this->client->setTimeout(2);
        try {
            $this->client->receive(30);
            $this->fail('Second receiving had to fail.');
        } catch (SocketException $e) {
            $this->assertSame(4, $e->getCode(), 'Improper exception code.');
        }
    }

    public function testClientTimingOutStream()
    {
        $this->assertSame('aaa', $this->client->receive(3));
        $this->client->setTimeout(2);
        try {
            $this->client->receiveStream(30);
            $this->fail('Second receiving had to fail.');
        } catch (SocketException $e) {
            $this->assertSame(5, $e->getCode(), 'Improper exception code.');
        }
    }

    public function testSetBuffer()
    {
        $this->assertFalse($this->client->setBuffer(0, 'unknown direction'));
        $this->assertFalse($this->client->setBuffer(-1));
        $this->assertTrue(
            $this->client->setBuffer(99, Stream::DIRECTION_RECEIVE)
        );
    }

    public function testSetChunk()
    {
        $defaultChunks = $this->client->getChunk();
        $this->assertInternalType('array', $defaultChunks);
        
        $this->assertFalse($this->client->getChunk('unknown direction'));
        $this->assertFalse($this->client->setChunk(1, 'unknown direction'));
        
        $this->assertFalse($this->client->setChunk(0));
        $this->assertFalse($this->client->setChunk(0, Stream::DIRECTION_ALL));
        $this->assertFalse($this->client->setChunk(0, Stream::DIRECTION_SEND));
        $this->assertFalse(
            $this->client->setChunk(0, Stream::DIRECTION_RECEIVE)
        );
        
        $this->assertTrue(
            $this->client->setChunk(1, Stream::DIRECTION_RECEIVE)
        );
        $this->assertSame(
            1,
            $this->client->getChunk(Stream::DIRECTION_RECEIVE)
        );
        $this->assertSame(
            $defaultChunks[Stream::DIRECTION_SEND],
            $this->client->getChunk(Stream::DIRECTION_SEND)
        );
        $this->assertSame(
            array(
                Stream::DIRECTION_SEND
                    => $defaultChunks[Stream::DIRECTION_SEND],
                Stream::DIRECTION_RECEIVE
                    => 1
            ),
            $this->client->getChunk()
        );
        
        $this->assertTrue(
            $this->client->setChunk(1, Stream::DIRECTION_SEND)
        );
        $this->assertSame(
            1,
            $this->client->getChunk(Stream::DIRECTION_SEND)
        );
        $this->assertSame(
            1,
            $this->client->getChunk(Stream::DIRECTION_RECEIVE)
        );
        $this->assertSame(
            array(
                Stream::DIRECTION_SEND => 1,
                Stream::DIRECTION_RECEIVE => 1
            ),
            $this->client->getChunk()
        );
        
        $this->assertTrue(
            $this->client->setChunk(2)
        );
        $this->assertSame(
            2,
            $this->client->getChunk(Stream::DIRECTION_SEND)
        );
        $this->assertSame(
            2,
            $this->client->getChunk(Stream::DIRECTION_RECEIVE)
        );
        $this->assertSame(
            array(
                Stream::DIRECTION_SEND => 2,
                Stream::DIRECTION_RECEIVE => 2
            ),
            $this->client->getChunk()
        );
    }

    public function testShutdown()
    {
        $this->client->send('bbb');
        $this->assertSame('bbb', $this->client->receive(3));
        $this->assertFalse($this->client->shutdown('undefined direction'));
        $this->assertTrue($this->client->shutdown(Stream::DIRECTION_SEND));
        try {
            $this->client->send('b');
            $this->fail('Sending had to fail.');
        } catch (SocketException $e) {
            $this->assertSame(3, $e->getCode(), 'Improper exception code.');
        }
    }
}
