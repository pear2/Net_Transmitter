<?php

namespace PEAR2\Net\Transmitter;

require_once 'ServerTest.php';

class ServerEncryptedTest extends ServerTest
{
    public static function setUpBeforeClass()
    {
        ini_set('display_errors', 'On');
        $hostname = strpos(LOCAL_HOSTNAME, ':') !== false
            ? '[' . LOCAL_HOSTNAME . ']' : LOCAL_HOSTNAME;
        static::$server = stream_socket_server(
            "tls://{$hostname}:" . LOCAL_PORT,
            static::$errorno,
            static::$errstr,
            STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
            stream_context_create(
                array(
                    'ssl' => array(
                        'ciphers' => 'ADH',
                        //'local_cert' => 'D:\Users\boen_robot\Desktop\test.pem',
                        //'passphrase' => '',
                       // 'cafile' => 'D:\Users\boen_robot\Desktop\test.pem'
                        
                    )
                )
            )
        );
        return;
    }
    
    public static function tearDownAfterClass()
    {
        fclose(static::$server);
    }
}
