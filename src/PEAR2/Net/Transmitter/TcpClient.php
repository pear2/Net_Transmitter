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
 * @version   SVN: $WCREV$
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
class TcpClient extends Stream
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
            parent::__construct(
                @stream_socket_client(
                    "tcp://{$host}:{$port}/{$key}", $this->error_no,
                    $this->error_str, $timeout, $flags, $context
                )
            );
        } catch (\Exception $e) {
            throw $this->createException('Failed to initialize socket.', 7);
        }
    }

    /**
     * Checks whether there is data to be read from the socket.
     * 
     * @return bool TRUE if there is data to be read, FALSE otherwise.
     */
    public function isDataAwaiting()
    {
        if (parent::isDataAwaiting()) {
            $meta = stream_get_meta_data($this->stream);
            return!$meta['timed_out'] && !$meta['eof'];
        }
        return false;
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

}