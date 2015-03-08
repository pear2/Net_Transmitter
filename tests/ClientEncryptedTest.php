<?php

namespace PEAR2\Net\Transmitter;

require_once 'ClientTest.php';

class ClientEncryptedTest extends ClientTest
{
    public function setUp()
    {
        $this->client = new TcpClient(
            REMOTE_HOSTNAME,
            REMOTE_PORT,
            false,
            null,
            '',
            NetworkStream::CRYPTO_TLS,
            stream_context_create(
                array(
                    'ssl' => array(
                        'verify_peer'
                            => true,
                        'verify_peer_name'
                            => false,
                        'allow_self_signed'
                            => true,
                        'cafile'
                            => __DIR__ . DIRECTORY_SEPARATOR .
                                CERTIFICATE_FILE . '.cer'
                    )
                )
            )
        );
    }
}
