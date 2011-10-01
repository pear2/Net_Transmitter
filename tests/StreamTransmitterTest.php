<?php
namespace PEAR2\Net\Transmitter;

class RequestHandlingTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaultStreamTransmitterException()
    {
        try {
            $trans = new StreamTransmitter('invalid arg');
            $this->fail('Transmitter initialization had to fail.');
        } catch (\Exception $e) {
            $this->assertEquals(1, $e->getCode(), 'Improper exception code.');
        }
    }
}