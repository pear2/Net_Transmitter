<?php

namespace PEAR2\Net\Transmitter;

require_once 'ServerTest.php';

/**
 * @group Server
 * @group Encrypted
 * 
 * @requires extension openssl
 */
class ServerEncryptedTest extends ServerTest
{
    public static function setUpBeforeClass()
    {
        //Start the server
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
                        'verify_peer'
                            => false,
                        'verify_peer_name'
                            => false,
                        'local_cert'
                            => __DIR__ . DIRECTORY_SEPARATOR .
                                CERTIFICATE_FILE . '.cer',
                        'cafile'
                            => __DIR__ . DIRECTORY_SEPARATOR .
                                CERTIFICATE_FILE . '.cer'
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
