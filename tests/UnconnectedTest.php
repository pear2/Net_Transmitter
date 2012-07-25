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
    public function testDefaultStreamTransmitterException()
    {
        try {
            $trans = new Stream('invalid arg');
            $this->fail('Transmitter initialization had to fail.');
        } catch (StreamException $e) {
            $this->assertEquals(1, $e->getCode(), 'Improper exception code.');
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
        $this->assertEquals(0, count($filters));
    }
    
    public function testFilterCollection()
    {
        $filters = new FilterCollection();
        $this->assertEquals(0, count($filters));
        $filters->append('string.toupper');
        $this->assertEquals(1, count($filters));
        $filters->append('string.rot13');
        $this->assertEquals(2, count($filters));
        $filters->clear();
        $this->assertEquals(0, count($filters));
        
        $filters->append('string.toupper');
        $this->assertEquals(1, count($filters));
        $this->assertEquals('string.toupper', $filters->key());
        $this->assertEquals(array(), $filters->current());
        $filters->insertBefore(-1, 'string.rot13');
        $filters->insertBefore(0, 'string.base64');
        $filters->insertBefore(1, 'string.tolower');
        $filters->insertBefore(count($filters) + 2, 'string.quoted-printable');
        
        $this->assertEquals('string.base64', $filters->key());
        $this->assertEquals(array(), $filters->current());
        $this->assertEquals(0, $filters->getCurrentPosition());
        $this->assertTrue($filters->next());
        $this->assertEquals('string.tolower', $filters->key());
        $this->assertEquals(array(), $filters->current());
        $this->assertEquals(1, $filters->getCurrentPosition());
        $this->assertTrue($filters->next());
        $this->assertEquals('string.rot13', $filters->key());
        $this->assertEquals(array(), $filters->current());
        $this->assertEquals(2, $filters->getCurrentPosition());
        $this->assertTrue($filters->next());
        $this->assertEquals('string.toupper', $filters->key());
        $this->assertEquals(array(), $filters->current());
        $this->assertEquals(3, $filters->getCurrentPosition());
        $this->assertTrue($filters->next());
        $this->assertEquals('string.quoted-printable', $filters->key());
        $this->assertEquals(array(), $filters->current());
        $this->assertEquals(4, $filters->getCurrentPosition());
        
        $this->assertTrue($filters->prev());
        $this->assertEquals('string.toupper', $filters->key());
        $this->assertEquals(array(), $filters->current());
        $this->assertEquals(3, $filters->getCurrentPosition());
        
        $this->assertTrue($filters->rewind());
        $filters->removeAt(2);
        
        $this->assertEquals('string.base64', $filters->key());
        $this->assertEquals(array(), $filters->current());
        $this->assertEquals(0, $filters->getCurrentPosition());
        $this->assertTrue($filters->next());
        $this->assertEquals('string.tolower', $filters->key());
        $this->assertEquals(array(), $filters->current());
        $this->assertEquals(1, $filters->getCurrentPosition());
        $this->assertTrue($filters->next());
        $this->assertEquals('string.toupper', $filters->key());
        $this->assertEquals(array(), $filters->current());
        $this->assertEquals(2, $filters->getCurrentPosition());
        $this->assertTrue($filters->next());
        $this->assertEquals('string.quoted-printable', $filters->key());
        $this->assertEquals(array(), $filters->current());
        $this->assertEquals(3, $filters->getCurrentPosition());
    }
    
    public function testInvalidContext()
    {
        try {
            new C(
                REMOTE_HOSTNAME, REMOTE_PORT, false, null, '',
                fopen('php://input', 'r')
            );
            $this->fail('Client creation had to fail.');
        } catch(SocketException $e)
        {
            $this->assertEquals(6, $e->getCode(), 'Improper exception code.');
        }
    }
    
    public function testSilence()
    {
        try {
            new C(SILENT_HOSTNAME, REMOTE_PORT);
            $this->fail('Client creation had to fail.');
        } catch(SocketException $e)
        {
            $this->assertEquals(7, $e->getCode(), 'Improper exception code.');
            $this->assertEquals(
                10061, $e->getSocketErrorNumber(), 'Improper exception code.'
            );
        }
        try {
            new C(REMOTE_HOSTNAME, SILENT_PORT);
            $this->fail('Client creation had to fail.');
        } catch(SocketException $e)
        {
            $this->assertEquals(7, $e->getCode(), 'Improper exception code.');
            $this->assertEquals(
                10061, $e->getSocketErrorNumber(), 'Improper exception code.'
            );
        }
    }
    
    public function testInvalidServer()
    {
        try {
            new SC('not a server', 1/*h*/ * 60/*m*/ * 60/*s*/);
            $this->fail('Server creation had to fail.');
        } catch(SocketException $e)
        {
            $this->assertEquals(8, $e->getCode(), 'Improper exception code.');
        }
    }
    
    public function testServerConnectionTimeout()
    {
        try {
            new SC(
                stream_socket_server(
                    'tcp://' . LOCAL_HOSTNAME . ':' . LOCAL_PORT
                ),
                2
            );
            $this->fail('Server creation had to fail.');
        } catch(SocketException $e)
        {
            $this->assertEquals(9, $e->getCode(), 'Improper exception code.');
        }
    }
}