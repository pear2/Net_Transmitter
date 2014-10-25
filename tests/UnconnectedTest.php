<?php
namespace PEAR2\Net\Transmitter;

use PEAR2\Net\Transmitter\TcpClient as C;
use PEAR2\Net\Transmitter\TcpServerConnection as SC;

class UnconnectedTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var int
     */
    public static $defaultSocketTimeout;

    public static function setUpBeforeClass()
    {
        self::$defaultSocketTimeout = ini_set('default_socket_timeout', 2);
    }

    public static function tearDownAfterClass()
    {
        ini_set('default_socket_timeout', self::$defaultSocketTimeout);
    }

    /**
     * N/A
     * 
     * @return void
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function testDefaultStreamTransmitterException()
    {
        try {
            $trans = new Stream('invalid arg');
            $this->fail('Transmitter initialization had to fail.');
        } catch (StreamException $e) {
            $this->assertSame(1, $e->getCode(), 'Improper exception code.');
        }
    }

    public function testEmptyFilterCollection()
    {
        $filters = new FilterCollection();
        $this->assertFalse($filters->rewind());
        $this->assertFalse($filters->end());
        $this->assertFalse($filters->key());
        $this->assertFalse($filters->current());
        $this->assertFalse($filters->valid());
        $this->assertSame(0, count($filters));
    }

    public function testFilterCollection()
    {
        $filters = new FilterCollection();
        $this->assertSame(0, count($filters));
        $filters->append('string.toupper');
        $this->assertSame(1, count($filters));
        $filters->append('string.rot13');
        $this->assertSame(2, count($filters));
        $filters->clear();
        $this->assertSame(0, count($filters));

        $filters->append('string.toupper');
        $this->assertSame(1, count($filters));
        $this->assertSame('string.toupper', $filters->key());
        $this->assertSame(array(), $filters->current());
        $filters->insertBefore(-1, 'string.rot13');
        $filters->insertBefore(0, 'string.base64');
        $filters->insertBefore(1, 'string.tolower');
        $filters->insertBefore(count($filters) + 2, 'string.quoted-printable');

        $this->assertSame('string.base64', $filters->key());
        $this->assertSame(array(), $filters->current());
        $this->assertSame(0, $filters->getCurrentPosition());
        $this->assertTrue($filters->next());
        $this->assertSame('string.tolower', $filters->key());
        $this->assertSame(array(), $filters->current());
        $this->assertSame(1, $filters->getCurrentPosition());
        $this->assertTrue($filters->next());
        $this->assertSame('string.rot13', $filters->key());
        $this->assertSame(array(), $filters->current());
        $this->assertSame(2, $filters->getCurrentPosition());
        $this->assertTrue($filters->next());
        $this->assertSame('string.toupper', $filters->key());
        $this->assertSame(array(), $filters->current());
        $this->assertSame(3, $filters->getCurrentPosition());
        $this->assertTrue($filters->next());
        $this->assertSame('string.quoted-printable', $filters->key());
        $this->assertSame(array(), $filters->current());
        $this->assertSame(4, $filters->getCurrentPosition());

        $this->assertTrue($filters->prev());
        $this->assertSame('string.toupper', $filters->key());
        $this->assertSame(array(), $filters->current());
        $this->assertSame(3, $filters->getCurrentPosition());

        $this->assertTrue($filters->rewind());
        $filters->removeAt(2);

        $this->assertSame('string.base64', $filters->key());
        $this->assertSame(array(), $filters->current());
        $this->assertSame(0, $filters->getCurrentPosition());
        $this->assertTrue($filters->next());
        $this->assertSame('string.tolower', $filters->key());
        $this->assertSame(array(), $filters->current());
        $this->assertSame(1, $filters->getCurrentPosition());
        $this->assertTrue($filters->next());
        $this->assertSame('string.toupper', $filters->key());
        $this->assertSame(array(), $filters->current());
        $this->assertSame(2, $filters->getCurrentPosition());
        $this->assertTrue($filters->next());
        $this->assertSame('string.quoted-printable', $filters->key());
        $this->assertSame(array(), $filters->current());
        $this->assertSame(3, $filters->getCurrentPosition());
    }

    public function testInvalidContext()
    {
        try {
            new C(
                REMOTE_HOSTNAME,
                REMOTE_PORT,
                false,
                null,
                '',
                NetworkStream::CRYPTO_OFF,
                fopen('php://input', 'r')
            );
            $this->fail('Client creation had to fail.');
        } catch (SocketException $e) {
            $this->assertSame(6, $e->getCode(), 'Improper exception code.');
        }
    }

    public function testSilence()
    {
        $expectedCode = strpos(PHP_OS, 'WIN') === 0 ? 10061 : 111;
        try {
            new C(SILENT_HOSTNAME, REMOTE_PORT);
            $this->fail('Client creation had to fail.');
        } catch (SocketException $e) {
            $this->assertSame(8, $e->getCode(), 'Improper exception code.');
            $this->assertSame(
                $expectedCode,
                $e->getSocketErrorNumber(),
                'Improper exception code.'
            );
        }
        try {
            new C(REMOTE_HOSTNAME, SILENT_PORT);
            $this->fail('Client creation had to fail.');
        } catch (SocketException $e) {
            $this->assertSame(8, $e->getCode(), 'Improper exception code.');
            $this->assertSame(
                $expectedCode,
                $e->getSocketErrorNumber(),
                'Improper exception code.'
            );
        }
    }

    public function testInvalidClient()
    {
        try {
            new C('@', REMOTE_PORT);
            $this->fail('Client creation had to fail.');
        } catch (SocketException $e) {
            $this->assertSame(7, $e->getCode(), 'Improper exception code.');
            $this->assertSame(
                0,
                $e->getSocketErrorNumber(),
                'Improper exception code.'
            );
        }
    }

    public function testInvalidServer()
    {
        try {
            new SC('not a server', 1/*h*/ * 60/*m*/ * 60/*s*/);
            $this->fail('Server creation had to fail.');
        } catch (SocketException $e) {
            $this->assertSame(9, $e->getCode(), 'Improper exception code.');
        }
    }

    public function testServerConnectionTimeout()
    {
        $hostname = strpos(LOCAL_HOSTNAME, ':') !== false
            ? '[' . LOCAL_HOSTNAME . ']' : LOCAL_HOSTNAME;
        try {
            new SC(
                stream_socket_server(
                    "tcp://{$hostname}:" . LOCAL_PORT
                ),
                2
            );
            $this->fail('Server creation had to fail.');
        } catch (SocketException $e) {
            $this->assertSame(10, $e->getCode(), 'Improper exception code.');
        }
    }
}
