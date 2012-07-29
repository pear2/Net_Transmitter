<?php

/**
 * ~~summary~~
 * 
 * ~~description~~
 * 
 * PHP version 5
 * 
 * @category  Net
 * @package   PEAR2_Net_Transmitter
 * @author    Vasil Rangelov <boen.robot@gmail.com>
 * @copyright 2011 Vasil Rangelov
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @version   GIT: $Id$
 * @link      http://pear2.php.net/PEAR2_Net_Transmitter
 */
/**
 * The namespace declaration.
 */
namespace PEAR2\Net\Transmitter;

/**
 * Exception thrown when something goes wrong with the connection.
 * 
 * @category Net
 * @package  PEAR2_Net_Transmitter
 * @author   Vasil Rangelov <boen.robot@gmail.com>
 * @license  http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @link     http://pear2.php.net/PEAR2_Net_Transmitter
 */
class SocketException extends \RuntimeException implements Exception
{

    /**
     * @var int The system level error code.
     */
    protected $error_no;

    /**
     * @var string The system level error message.
     */
    protected $error_str;

    /**
     * Creates a new socket exception.
     * 
     * @param string     $message   The Exception message to throw.
     * @param int        $code      The Exception code.
     * @param \Exception $previous  The previous exception used for the
     * exception chaining.
     * @param int        $error_no  The system level error number.
     * @param string     $error_str The system level error message.
     */
    public function __construct($message = '', $code = 0, $previous = null,
        $error_no = null, $error_str = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->error_no = $error_no;
        $this->error_str = $error_str;
    }

    /**
     * Gets the system level error code on the socket.
     * 
     * @return int The system level error number.
     */
    public function getSocketErrorNumber()
    {
        return $this->error_no;
    }

    // @codeCoverageIgnoreStart
    // Unreliable in testing.

    /**
     * Gets the system level error message on the socket.
     * 
     * @return string The system level error message.
     */
    public function getSocketErrorMessage()
    {
        return $this->error_str;
    }

    /**
     * Returns a string representation of the exception.
     * 
     * @return string The exception as a string.
     */
    public function __toString()
    {
        $result = parent::__toString();
        if (null !== $this->getSocketErrorNumber()) {
            $result .= "\nSocket error number:" . $this->getSocketErrorNumber();
        }
        if (null !== $this->getSocketErrorMessage()) {
            $result .= "\nSocket error message:"
                . $this->getSocketErrorMessage();
        }
        return $result;
    }

    // @codeCoverageIgnoreEnd
}