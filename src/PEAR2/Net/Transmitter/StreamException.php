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
 * Base for this exception.
 */
use RuntimeException;

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
class StreamException extends RuntimeException implements Exception
{
    const CODE_INVALID             = 0x0001;
    const CODE_FAIL                = 0x0002;
    const CODE_SEND                = 0x0010;
    const CODE_RECEIVE             = 0x0020;
    const CODE_STREAM              = 0x0100;
    const CODE_STRING              = 0x0200;

    const CODE_INVALID_STREAM      = 0x0101;
    const CODE_STREAM_SEND_FAIL    = 0x0112;
    const CODE_STRING_SEND_FAIL    = 0x0212;
    const CODE_STREAM_RECEIVE_FAIL = 0x0122;
    const CODE_STRING_RECEIVE_FAIL = 0x0222;

    /**
     * The fragment up until the point of failure.
     *
     * On failure with sending, this is the number of bytes sent successfully
     * before the failure.
     * On failure when receiving, this is a string/stream holding the contents
     * received successfully before the failure.
     * NULL if the failure occurred before the operation started.
     *
     * @var int|string|resource|null
     */
    protected $fragment = null;

    /**
     * Creates a new stream exception.
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
     */
    public function __construct(
        $message,
        $code,
        E $previous = null,
        $fragment = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->fragment = $fragment;
    }

    /**
     * Gets the stream fragment.
     *
     * @return int|string|resource|null The fragment up until the
     *     point of failure.
     *     On failure with sending, this is the number of bytes sent
     *     successfully before the failure.
     *     On failure when receiving, this is a string/stream holding
     *     the contents received successfully before the failure.
     *     NULL if the failure occurred before the operation started.
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    // @codeCoverageIgnoreStart
    // Unreliable in testing.

    /**
     * Returns a string representation of the exception.
     *
     * @return string The exception as a string.
     */
    public function __toString()
    {
        $result = parent::__toString();
        if (null !== $this->fragment) {
            $result .= "\nFragment: ";
            if (is_scalar($this->fragment)) {
                $result .= (string)$this->fragment;
            } else {
                $result .= stream_get_contents($this->fragment);
            }
        }
        return $result;
    }
    // @codeCoverageIgnoreEnd
}
