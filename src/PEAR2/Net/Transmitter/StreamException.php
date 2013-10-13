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

use RuntimeException;
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
    /**
     * @var string|null The fragment up until the point of failure.
     *     NULL if the failure occured before the operation started.
     */
    protected $fragment = null;

    /**
     * Creates a new stream exception.
     * 
     * @param string $message  The Exception message to throw.
     * @param int    $code     The Exception code.
     * @param E|null $previous The previous exception used for the
     *     exception chaining.
     * @param mixed  $fragment On failure with sending, this is the number
     *     of bytes sent successfully before the failure.
     *     On failure when receiving, this is a string holding the contents
     *     received successfully before the failure.
     *     NULL if the failure occured before the operation started.
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
     * @return string|null The fragment up until the point of failure.
     *     NULL if the failure occured before the operation started.
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
            $result .= "\nFragment: " . $this->fragment;
        }
        return $result;
    }
    // @codeCoverageIgnoreEnd
}
