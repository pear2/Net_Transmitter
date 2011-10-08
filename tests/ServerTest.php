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
    }
    
    public function tearDown()
    {
        unset($this->conn);
    }
    
    public function testOneByteEcho()
    {
        $this->assertEquals(
            1, $this->conn->send($this->conn->receive(1)), 'Wrong byte echoed.'
        );
    }
}