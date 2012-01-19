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
    
    protected $persistentId = null;
    
    protected $persistentHandler = null;

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

        try {
            $uri = "tcp://{$host}:{$port}/{$key}";
            parent::__construct(
                @stream_socket_client(
                    $uri, $this->error_no,
                    $this->error_str, $timeout, $flags, $context
                )
            );
            $this->persistentId
                = str_replace(
                    array('!' , '|', '/', '\\', '<', '>', '?', '*', '"'),
                    array('~!', '!', '!', '!' , '!', '!', '!', '!', '!'),
                    __NAMESPACE__ . '\TcpClient ' . $uri
                ) . ' ';
            if (version_compare(phpversion('wincache'), '1.1.0', '>=')) {
                $this->persistentHandler = 'wincache';
            }
        } catch (\Exception $e) {
            throw $this->createException('Failed to initialize socket.', 7);
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
    
    protected function lock($key)
    {
        if ($this->persist) {
            switch($this->persistentHandler) {
            case 'wincache':
                return wincache_lock($this->persistentId . $key);
            default:
                throw $this->createException(
                    'Make sure WinCache is enabled.', 8
                );
            }
        }
        return true;
    }
    
    protected function unlock($key)
    {
        if ($this->persist) {
            switch($this->persistentHandler) {
            case 'wincache':
                return wincache_unlock($this->persistentId . $key);
            default:
                throw $this->createException(
                    'Make sure WinCache is enabled.', 8
                );
            }
        }
        return true;
    }
    
    public function receive($length, $what = 'data')
    {
        if ($this->lock('r')) {
            $result = parent::receive($length, $what);
            $this->unlock('r');
            return $result;
        } else {
            throw $this->createException(
                'Unable to obtain receiving lock', 9
            );
        }
    }
    
    public function receiveStream(
        $length, FilterCollection $filters = null, $what = 'stream data'
    ) {
        if ($this->lock('r')) {
            $result = parent::receiveStream($length, $filters, $what);
            $this->unlock('r');
            return $result;
        } else {
            throw $this->createException(
                'Unable to obtain receiving lock', 9
            );
        }
    }
    
    public function send($contents, $offset = null, $length = null)
    {
        if ($this->lock('w')) {
            $result = parent::send($contents, $offset, $length);
            $this->unlock('w');
            return $result;
        } else {
            throw $this->createException(
                'Unable to obtain sending lock', 10
            );
        }
    }

}