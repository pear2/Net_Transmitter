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
 * Used to enable any exception in chaining.
 */
use Exception as E;

/**
 * Exception thrown when something goes wrong with the connection.
 *
 * @category Net
 * @package  PEAR2_Net_Transmitter
 * @author   Vasil Rangelov <boen.robot@gmail.com>
 * @license  http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @link     http://pear2.php.net/PEAR2_Net_Transmitter
 */
class SocketException extends StreamException
{
    const CODE_CLIENT              = 0x0400;
    const CODE_CONTEXT             = 0x0800;
    const CODE_SERVER              = 0x1000;
    const CODE_INIT                = 0x0040;
    const CODE_CONNECT             = 0x0080;

    const CODE_INVALID_CONTEXT     = 0x0801;
    const CODE_CLIENT_INIT_FAIL    = 0x0442;
    const CODE_CLIENT_CONNECT_FAIL = 0x0482;
    const CODE_INVALID_SERVER      = 0x1001;
    const CODE_SERVER_CONNECT_FAIL = 0x1082;

    /**
     * The system level error code.
     *
     * @var int|null
     */
    protected $errorNo;

    /**
     * The system level error message.
     *
     * @var string|null
     */
    protected $errorStr;

    /**
     * Creates a new socket exception.
     *
     * @param string                   $message  The Exception message to throw.
     * @param int                      $code     The Exception code.
     * @param E|null                   $previous Previous exception thrown,
     *     or NULL if there is none.
     * @param int|string|resource|null $fragment The fragment up until the
     *     point of failure.
     *     On failure with sending, this is the number of bytes sent
     *     successfully before the failure.
     *     On failure when receiving, this is a string/stream holding
     *     the contents received successfully before the failure.
     *     NULL if the failure occurred before the operation started.
     * @param int|null                 $errorNo  The system level error number.
     * @param string|null              $errorStr The system level
     *     error message.
     */
    public function __construct(
        $message = '',
        $code = 0,
        E $previous = null,
        $fragment = null,
        $errorNo = null,
        $errorStr = null
    ) {
        parent::__construct($message, $code, $previous, $fragment);
        $this->errorNo = $errorNo;
        $this->errorStr = $errorStr;
    }

    /**
     * Gets the system level error code on the socket.
     *
     * @return int|null The system level error number.
     */
    public function getSocketErrorNumber()
    {
        return $this->errorNo;
    }

    // @codeCoverageIgnoreStart
    // Unreliable in testing.

    /**
     * Gets the system level error message on the socket.
     *
     * @return string|null The system level error message.
     */
    public function getSocketErrorMessage()
    {
        return $this->errorStr;
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
