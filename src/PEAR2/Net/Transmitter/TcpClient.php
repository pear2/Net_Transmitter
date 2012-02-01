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
 * Used for managing persistent connections.
 */
use PEAR2\Cache\SHM;

/**
 * A socket transmitter.
 * 
 * This is a convinience wrapper for socket functionality. Used to ensure data
 * integrity.
 * 
 * @category Net
 * @package  PEAR2_Net_Transmitter
 * @author   Vasil Rangelov <boen.robot@gmail.com>
 * @license  http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @link     http://pear2.php.net/PEAR2_Net_Transmitter
 */
class TcpClient extends NetworkStream
{

    /**
     * @var int The error code of the last error on the socket.
     */
    protected $error_no = null;

    /**
     * @var string The error message of the last error on the socket.
     */
    protected $error_str = null;
    
    /**
     * @var SHM Persistent connection handler. Remains NULL for non-persistent
     * connections. 
     */
    protected $persistentHandler = null;
    
    /**
     * @var int A bitmask with the locked directions. 
     */
    protected $lockState = self::DIRECTION_NONE;

    /**
     * Creates a new connection with the specified options.
     * 
     * @param string   $host    Hostname (IP or domain) of the server.
     * @param int      $port    The port on the server.
     * @param bool     $persist Whether or not the connection should be a
     * persistent one.
     * @param float    $timeout The timeout for the connection.
     * @param string   $key     A string that uniquely identifies the
     * connection.
     * @param resource $context A context for the socket.
     */
    public function __construct($host, $port, $persist = false,
        $timeout = null, $key = '', $context = null
    ) {
        if (strpos($host, ':') !== false) {
            $host = "[{$host}]";
        }
        $flags = STREAM_CLIENT_CONNECT;
        if ($persist) {
            $flags |= STREAM_CLIENT_PERSISTENT;
        }

        $timeout
            = null == $timeout ? ini_get('default_socket_timeout') : $timeout;

        $key = rawurlencode($key);

        if (null === $context) {
            $context = stream_context_get_default();
        } elseif (
            (!is_resource($context))
            || ('stream-context' !== get_resource_type($context))
        ) {
            throw $this->createException('Invalid context supplied.', 6);
        }
        $uri = "tcp://{$host}:{$port}/{$key}";
        try {
            parent::__construct(
                @stream_socket_client(
                    $uri, $this->error_no, $this->error_str,
                    $timeout, $flags, $context
                )
            );
        } catch (\Exception $e) {
            throw $this->createException('Failed to initialize socket.', 7);
        }
        if ($persist) {
            $this->persistentHandler = new SHM(
                'PEAR2\Net\Transmitter\TcpClient ' . $uri . ' '
            );
        }
    }

    /**
     * Creates a new exception.
     * 
     * Creates a new exception. Used by the rest of the functions in this class.
     * 
     * @param string $message The exception message.
     * @param int    $code    The exception code.
     * 
     * @return SocketException The exception to then be thrown.
     */
    protected function createException($message, $code = 0)
    {
        return new SocketException(
            $message, $code, null, $this->error_no, $this->error_str
        );
    }
    
    /**
     * Locks transmission.
     * 
     * Locks transmission in one or more directions. Useful when dealing with
     * persistent connections. Note that every send/receive call implicitly
     * calls this function and then restores it to the previous state. You only
     * need to call this function if you need to do an uninterrputed sequence of
     * such calls.
     * 
     * @param int $direction The direction(s) to have locked. Acceptable values
     * are the DIRECTION_* constants.
     * 
     * @return int The previous state or FALSE on failure.
     */
    public function lock($direction = self::DIRECTION_ALL)
    {
        if ($this->persist) {
            $result = $this->lockState;
            if ($direction & self::DIRECTION_RECEIVE) {
                if (($this->lockState & self::DIRECTION_RECEIVE)
                    || $this->persistentHandler->lock(self::DIRECTION_RECEIVE)
                ) {
                    $result |= self::DIRECTION_RECEIVE;
                } else {
                    return false;
                }
            } else {
                if ($this->persistentHandler->unlock(self::DIRECTION_RECEIVE)) {
                    $result |= ~self::DIRECTION_RECEIVE;
                } else {
                    return false;
                }
            }
            
            if ($direction & self::DIRECTION_SEND) {
                if (($this->lockState & self::DIRECTION_SEND)
                    || $this->persistentHandler->lock(self::DIRECTION_SEND)
                ) {
                    $result |= self::DIRECTION_SEND;
                } else {
                    return false;
                }
            } else {
                if ($this->persistentHandler->unlock(self::DIRECTION_SEND)) {
                    $result |= ~self::DIRECTION_SEND;
                } else {
                    return false;
                }
            }
            $oldState = $this->lockState;
            $this->lockState = $result;
            return $oldState;
        }
        return false;
    }
    
    public function receive($length, $what = 'data')
    {
        $previousState = $this->lock(self::DIRECTION_RECEIVE);
        if ($this->persist && false === $previousState) {
            throw $this->createException(
                'Unable to obtain receiving lock', 9
            );
        }
        $result = parent::receive($length, $what);
        $this->lock($previousState);
        return $result;
    }
    
    public function receiveStream(
        $length, FilterCollection $filters = null, $what = 'stream data'
    ) {
        $previousState = $this->lock(self::DIRECTION_RECEIVE);
        if ($this->persist && false === $previousState) {
            throw $this->createException(
                'Unable to obtain receiving lock', 9
            );
        }
        $result = parent::receiveStream($length, $filters, $what);
        $this->lock($previousState);
        return $result;
    }
    
    public function send($contents, $offset = null, $length = null)
    {
        $previousState = $this->lock(self::DIRECTION_SEND);
        if ($this->persist && false === $previousState) {
            throw $this->createException(
                'Unable to obtain sending lock', 10
            );
        }
        $result = parent::send($contents, $offset, $length);
        $this->lock($previousState);
        return $result;
    }

}