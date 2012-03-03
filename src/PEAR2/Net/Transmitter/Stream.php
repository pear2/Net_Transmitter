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
 * A stream transmitter.
 * 
 * This is a convinience wrapper for stream functionality. Used to ensure data
 * integrity. Designed for TCP sockets, but it has intentionally been made to
 * accept any stream.
 * 
 * @category Net
 * @package  PEAR2_Net_Transmitter
 * @author   Vasil Rangelov <boen.robot@gmail.com>
 * @license  http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @link     http://pear2.php.net/PEAR2_Net_Transmitter
 */
class Stream
{
    /**
     * Used to stop settings in either direction being applied.
     */
    const DIRECTION_NONE = 0;
    /**
     * Used to apply settings only to receiving.
     */
    const DIRECTION_RECEIVE = 1;
    /**
     * Used to apply settings only to sending.
     */
    const DIRECTION_SEND = 2;
    /**
     * Used to apply settings to both sending and receiving.
     */
    const DIRECTION_ALL = 3;

    /**
     * @var resource The stream to wrap around.
     */
    protected $stream;

    /**
     * @var bool A flag that tells whether or not the stream is persistent.
     */
    protected $persist;
    
    /**
     * @var array An associative array with the chunk size of each direction.
     * Key is the direction, value is the size in bytes as integer.
     */
    protected $chunkSize = array(
        self::DIRECTION_SEND => 0xFFFFF, self::DIRECTION_RECEIVE => 0xFFFFF
    );

    /**
     * Wraps around the specified stream.
     * 
     * @param resource $stream The stream to wrap around.
     * 
     * @see isFresh()
     */
    public function __construct($stream)
    {
        if (!self::isStream($stream)) {
            throw $this->createException('Invalid stream supplied.', 1);
        }
        $this->stream = $stream;
        $this->persist = (bool) preg_match(
            '#\s?persistent\s?#sm', get_resource_type($stream)
        );
    }

    /**
     * Checks if a given variable is a stream resource.
     * 
     * @param mixed $var The variable to check.
     * 
     * @return bool TRUE on success, FALSE on failure.
     */
    public static function isStream($var)
    {
        return is_resource($var)
            && (bool) preg_match('#\s?stream$#sm', get_resource_type($var));
    }

    /**
     * Checks whether the wrapped stream is fresh.
     * 
     * Checks whether the wrapped stream is fresh. A stream is considered fresh
     * if there hasn't been any activity on it. Particularly useful for
     * detecting reused persistent connections.
     * 
     * @return bool TRUE if the socket is fresh, FALSE otherwise.
     */
    public function isFresh()
    {
        return ftell($this->stream) === 0;
    }
    
    /**
     * Checks whether the wrapped stream is a persistent one.
     * 
     * @return bool TRUE if the stream is a persistent one, FALSE otherwise. 
     */
    public function isPersistent()
    {
        return $this->persist;
    }
    
    /**
     * Sets the timeout for the stream.
     * 
     * @param int $seconds      Timeout in seconds.
     * @param int $microseconds Timeout in microseconds to be added to the
     * seconds.
     * 
     * @return bool TRUE on success, FALSE on failure.
     */
    public function setTimeout($seconds, $microseconds = 0)
    {
        return stream_set_timeout($this->stream, $seconds, $microseconds);
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
        switch($direction) {
        case self::DIRECTION_SEND:
            return stream_set_write_buffer($this->stream, $size) === 0;
        case self::DIRECTION_RECEIVE:
            return stream_set_read_buffer($this->stream, $size) === 0;
        case self::DIRECTION_ALL:
            return $this->setBuffer($size, self::DIRECTION_RECEIVE)
                && $this->setBuffer($size, self::DIRECTION_SEND);
        }
        return false;
    }
    
    /**
     * Sets the size of the chunk.
     * 
     * To ensure data integrity, as well as to allow for lower memory
     * consumption, data is sent/received in chunks. This function
     * allows you to set the size of each chunk. The default is 0xFFFFF.
     * 
     * @param int    $size      The desired size of the chunk, in bytes.
     * @param string $direction The chunk of which direction to set. Valid
     * values are the DIRECTION_* constants.
     * 
     * @return bool TRUE on success, FALSE on failure.
     */
    public function setChunk($size, $direction = self::DIRECTION_ALL)
    {
        $size = (int) $size;
        if ($size <= 0) {
            return false;
        }
        switch($direction) {
        case self::DIRECTION_SEND:
        case self::DIRECTION_RECEIVE:
            $this->chunkSize[$direction] = $size;
            return true;
        case self::DIRECTION_ALL:
            $this->chunkSize[self::DIRECTION_SEND]
                = $this->chunkSize[self::DIRECTION_RECEIVE] = $size;
            return true;
        }
        return false;
    }
    
    /**
     * Gets the size of the chunk.
     * 
     * @param string $direction The chunk of which direction to get. Valid
     * values are the DIRECTION_* constants.
     * 
     * @return int|array The chunk size in bytes, or an array of chunk sizes
     * with the directions as keys. FALSE on invalid direction. 
     */
    public function getChunk($direction = self::DIRECTION_ALL)
    {
        switch($direction) {
        case self::DIRECTION_SEND:
        case self::DIRECTION_RECEIVE:
            return $this->chunkSize[$direction];
        case self::DIRECTION_ALL:
            return $this->chunkSize;
        }
        return false;
    }

    /**
     * Sends a string or stream over the wrapped stream.
     * 
     * Sends a string or stream over the wrapped stream. If a seekable stream is
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
        $bytes = 0;
        $chunkSize = $this->chunkSize[self::DIRECTION_SEND];
        $lengthIsNotNull = null !== $length;
        $offsetIsNotNull = null !== $offset;
        if (self::isStream($contents)) {
            if ($offsetIsNotNull) {
                $oldPos = ftell($contents);
                fseek($contents, $offset, SEEK_SET);
            }
            while (!feof($contents)) {
                if ($this->isAcceptingData()) {
                    if ($lengthIsNotNull
                        && 0 === $chunkSize = min($chunkSize, $length - $bytes)
                    ) {
                        break;
                    }
                    $bytesNow = @stream_copy_to_stream(
                        $contents, $this->stream, $chunkSize
                    );
                    if (0 != $bytesNow) {
                        $bytes += $bytesNow;
                    } else {
                        throw $this->createException(
                            'Failed while sending stream.', 2
                        );
                    }
                }
            }
            if ($offsetIsNotNull) {
                fseek($contents, $oldPos, SEEK_SET);
            } else {
                fseek($contents, -$bytes, SEEK_CUR);
            }
        } else {
            $contents = (string) $contents;
            if ($offsetIsNotNull) {
                $contents = substr($contents, $offset);
            }
            if ($lengthIsNotNull) {
                $contents = substr($contents, 0, $length);
            }
            $bytesToSend = (double) sprintf('%u', strlen($contents));
            while ($bytes < $bytesToSend) {
                if ($this->isAcceptingData()) {
                    $bytesNow = @fwrite(
                        $this->stream, substr($contents, $bytes, $chunkSize)
                    );
                    if (0 != $bytesNow) {
                        $bytes += $bytesNow;
                    } else {
                        throw $this->createException(
                            'Failed while sending string.', 3
                        );
                    }
                }
            }
        }
        return $bytes;
    }

    /**
     * Reads from the wrapped stream to receive.
     * 
     * Reads from the wrapped stream to receive content as a string.
     * 
     * @param int    $length The number of bytes to receive.
     * @param string $what   Descriptive string about what is being received
     * (used in exception messages).
     * 
     * @return string The received content.
     */
    public function receive($length, $what = 'data')
    {
        $result = '';
        $chunkSize = $this->chunkSize[self::DIRECTION_RECEIVE];
        while ($length > 0) {
            if ($this->isAvailable()) {
                while ($this->isDataAwaiting()) {
                    $fragment = fread($this->stream, min($length, $chunkSize));
                    if ('' !== $fragment) {
                        $length -= strlen($fragment);
                        $result .= $fragment;
                        continue 2;
                    }
                }
            }
            throw $this->createException(
                "Failed while receiving {$what}", 4
            );
        }
        return $result;
    }

    /**
     * Reads from the wrapped stream to receive.
     * 
     * Reads from the wrapped stream to receive content as a stream.
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
        $result = fopen('php://temp', 'r+b');
        $appliedFilters = array();
        if (null !== $filters) {
            foreach ($filters as $filtername => $params) {
                $appliedFilters[] = stream_filter_append(
                    $result, $filtername, STREAM_FILTER_WRITE, $params
                );
            }
        }
        
        $chunkSize = $this->chunkSize[self::DIRECTION_RECEIVE];
        while ($length > 0) {
            if ($this->isAvailable()) {
                while ($this->isDataAwaiting()) {
                    $fragment = fread($this->stream, min($length, $chunkSize));
                    if ('' !== $fragment) {
                        $length -= strlen($fragment);
                        fwrite($result, $fragment);
                        continue 2;
                    }
                }
            }
            throw $this->createException(
                "Failed while receiving {$what}", 5
            );
        }
        
        foreach ($appliedFilters as $filter) {
            stream_filter_remove($filter);
        }
        rewind($result);
        return $result;
    }

    /**
     * Checks whether the stream is available for operations.
     * 
     * @return bool TRUE if the stream is available, FALSE otherwise.
     */
    public function isAvailable()
    {
        return self::isStream($this->stream) && !feof($this->stream);
    }

    /**
     * Checks whether there is data to be read from the wrapped stream.
     * 
     * @return bool TRUE if there is data to be read, FALSE otherwise.
     */
    public function isDataAwaiting()
    {
        return $this->isAvailable();
    }

    /**
     * Checks whether the wrapped stream can be written to without a block.
     * 
     * @return bool TRUE if the wrapped stream would not block on a write, FALSE
     * otherwise.
     * @SuppressWarnings(PHPMD.ShortVariable)
     */
    public function isAcceptingData()
    {
        $r = $e = null;
        $w = array($this->stream);
        return self::isStream($this->stream)
            && 1 === @/* due to PHP bug #54563 */stream_select($r, $w, $e, 0);
    }

    /**
     * Closes the opened stream, unless it's a persistent one.
     */
    public function __destruct()
    {
        if (!$this->persist) {
            $this->close();
        }
    }

    /**
     * Closes the opened stream, even if it is a persistent one.
     * 
     * @return bool TRUE on success, FALSE on failure.
     */
    public function close()
    {
        return self::isStream($this->stream) && fclose($this->stream);
    }

    /**
     * Creates a new exception.
     * 
     * Creates a new exception. Used by the rest of the functions in this class.
     * Override in derived classes for custom exception handling.
     * 
     * @param string $message The exception message.
     * @param int    $code    The exception code.
     * 
     * @return \Exception The exception to then be thrown.
     */
    protected function createException($message, $code = 0)
    {
        return new StreamException($message, $code);
    }

}