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
 * A network transmitter.
 * 
 * This is a convinience wrapper for network streams. Used to ensure data
 * integrity.
 * 
 * @category Net
 * @package  PEAR2_Net_Transmitter
 * @author   Vasil Rangelov <boen.robot@gmail.com>
 * @license  http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @link     http://pear2.php.net/PEAR2_Net_Transmitter
 */
class NetworkStream extends Stream
{

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
     * Sets the size of a stream's buffer.
     * 
     * @param int    $size      The desired size of the buffer, in bytes.
     * @param string $direction The buffer of which direction to set. Valid
     * values are the DIRECTION_* constants.
     * 
     * @return bool TRUE on success, FALSE on failure.
     */
    public function setBuffer($size, $direction = self::DIRECTION_ALL)
    {
        $result = parent::setBuffer($size, $direction);
        if (self::DIRECTION_SEND === $direction
            && function_exists('stream_set_chunk_size') && !$result
        ) {
            return false !== stream_set_chunk_size($this->stream, $size);
        }
        return $result;
    }
    
    /**
     * Shutdown a full-duplex connection
     * 
     * Shutdowns (partially or not) a full-duplex connection.
     * 
     * @param string $direction The direction for which to disable further
     * communications.
     * 
     * @return bool TRUE on success, FALSE on failure.
     */
    public function shutdown($direction = self::DIRECTION_ALL)
    {
        $directionMap = array(
            self::DIRECTION_ALL => STREAM_SHUT_RDWR,
            self::DIRECTION_SEND => STREAM_SHUT_WR,
            self::DIRECTION_RECEIVE => STREAM_SHUT_RD
        );
        return array_key_exists($direction, $directionMap)
            && stream_socket_shutdown($this->stream, $directionMap[$direction]);
    }
}
