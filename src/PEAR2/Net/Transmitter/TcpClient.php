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
    protected $error_no = 0;

    /**
     * @var string The error message of the last error on the socket.
     */
    protected $error_str = null;
    
    /**
     * @var SHM Persistent connection handler. Remains NULL for non-persistent
     * connections. 
     */
    protected $shmHandler = null;
    
    /**
     * @var array An array with all connections from this PHP request (as keys)
     * and their lock state (as a value). 
     */
    protected static $lockState = array();
    
    /**
     * @var string The URI of this connection.
     */
    protected $uri;

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
        $this->uri = "tcp://{$host}:{$port}/{$key}";
        try {
            parent::__construct(
                @stream_socket_client(
                    $this->uri, $this->error_no, $this->error_str,
                    $timeout, $flags, $context
                )
            );
        } catch (\Exception $e) {
            if (0 === $this->error_no) {
                throw $this->createException('Failed to initialize socket.', 7);
            }
            throw $this->createException('Failed to connect with socket.', 8);
        }
        if ($persist) {
            $this->shmHandler = SHM::factory(
                __CLASS__ . ' ' . $this->uri . ' '
            );
            self::$lockState[$this->uri] = self::DIRECTION_NONE;
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
     * @param int  $direction The direction(s) to have locked. Acceptable values
     * are the DIRECTION_* constants. If a lock for a direction can't be
     * obtained immediatly, the function will block until one is aquired. Note
     * that if you specify {@link DIRECTION_ALL}, the sending lock will be
     * obtained before the receiving one, and if obtaining the receiving lock
     * afterwards fails, the sending lock will be released too.
     * @param bool $replace   Whether to replace all locks with the specified
     * ones. Setting this to FALSE will make the function only obtain the locks
     * which are not already obtained.
     * 
     * @return int The previous state or FALSE if the connection is not
     * persistent or arguments are invalid.
     */
    public function lock($direction = self::DIRECTION_ALL, $replace = false)
    {
        if ($this->persist && is_int($direction)) {
            $old = self::$lockState[$this->uri];

            if ($direction & self::DIRECTION_SEND) {
                if (($old & self::DIRECTION_SEND)
                    || $this->shmHandler->lock(self::DIRECTION_SEND)
                ) {
                    self::$lockState[$this->uri] |= self::DIRECTION_SEND;
                } else {
                    throw new LockException('Unable to obtain sending lock.');
                }
            } elseif ($replace) {
                if (!($old & self::DIRECTION_SEND)
                    || $this->shmHandler->unlock(self::DIRECTION_SEND)
                ) {
                    self::$lockState[$this->uri] &= ~self::DIRECTION_SEND;
                } else {
                    throw new LockException('Unable to release sending lock.');
                }
            }
            
            try {
                if ($direction & self::DIRECTION_RECEIVE) {
                    if (($old & self::DIRECTION_RECEIVE)
                        || $this->shmHandler->lock(self::DIRECTION_RECEIVE)
                    ) {
                        self::$lockState[$this->uri] |= self::DIRECTION_RECEIVE;
                    } else {
                        throw new LockException(
                            'Unable to obtain receiving lock.'
                        );
                    }
                } elseif ($replace) {
                    if (!($old & self::DIRECTION_RECEIVE)
                        || $this->shmHandler->unlock(self::DIRECTION_RECEIVE)
                    ) {
                        self::$lockState[$this->uri]
                            &= ~self::DIRECTION_RECEIVE;
                    } else {
                        throw new LockException(
                            'Unable to release receiving lock.'
                        );
                    }
                }
            } catch(LockException $e) {
                if ($direction & self::DIRECTION_SEND
                    && !($old & self::DIRECTION_SEND)
                ) {
                    $this->shmHandler->unlock(self::DIRECTION_SEND);
                }
                throw $e;
            }
            return $old;
        }
        return false;
    }
    

    /**
     * Sends a string or stream to the server.
     * 
     * Sends a string or stream to the server. If a seekable stream is
     * provided, it will be seeked back to the same position it was passed as,
     * regardless of the $offset parameter.
     * 
     * @param string|resource $contents The string or stream to send.
     * @param int             $offset   The offset from which to start sending.
     * If a stream is provided, and this is set to NULL, sending will start from
     * the current stream position.
     * @param int             $length   The maximum length to send. If omitted,
     * the string/stream will be sent to its end.
     * 
     * @return int The number of bytes sent.
     */
    public function send($contents, $offset = null, $length = null)
    {
        if (false === ($previousState = $this->lock(self::DIRECTION_SEND))
            && $this->persist
        ) {
            throw $this->createException(
                'Unable to obtain sending lock', 10
            );
        }
        $result = parent::send($contents, $offset, $length);
        $this->lock($previousState, true);
        return $result;
    }

    /**
     * Receives data from the server.
     * 
     * Receives data from the server as a string.
     * 
     * @param int    $length The number of bytes to receive.
     * @param string $what   Descriptive string about what is being received
     * (used in exception messages).
     * 
     * @return string The received content.
     */
    public function receive($length, $what = 'data')
    {
        if (false === ($previousState = $this->lock(self::DIRECTION_RECEIVE))
            && $this->persist
        ) {
            throw $this->createException(
                'Unable to obtain receiving lock', 9
            );
        }
        $result = parent::receive($length, $what);
        $this->lock($previousState, true);
        return $result;
    }

    /**
     * Receives data from the server.
     * 
     * Receives data from the server as a stream.
     * 
     * @param int              $length  The number of bytes to receive.
     * @param FilterCollection $filters A collection of filters to apply to the
     * stream while receiving. Note that the filters will not be present on the
     * stream after receiving is done.
     * @param string           $what    Descriptive string about what is being
     * received (used in exception messages).
     * 
     * @return resource The received content.
     */
    public function receiveStream(
        $length, FilterCollection $filters = null, $what = 'stream data'
    ) {
        if (false === ($previousState = $this->lock(self::DIRECTION_RECEIVE))
            && $this->persist
        ) {
            throw $this->createException(
                'Unable to obtain receiving lock', 9
            );
        }
        $result = parent::receiveStream($length, $filters, $what);
        $this->lock($previousState, true);
        return $result;
    }
}