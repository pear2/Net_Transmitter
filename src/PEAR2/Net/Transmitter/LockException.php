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
 * Exception thrown when something goes wrong when dealing with locks.
 *
 * @category Net
 * @package  PEAR2_Net_Transmitter
 * @author   Vasil Rangelov <boen.robot@gmail.com>
 * @license  http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @link     http://pear2.php.net/PEAR2_Net_Transmitter
 */
class LockException extends \RuntimeException implements Exception
{
    const CODE_OBTAIN          = 0x010;
    const CODE_RELEASE         = 0x020;
    const CODE_SEND            = 0x001;
    const CODE_RECEIVE         = 0x002;
    const CODE_SEND_OBTAIN     = 0x011;
    const CODE_SEND_RELEASE    = 0x021;
    const CODE_RECEIVE_OBTAIN  = 0x012;
    const CODE_RECEIVE_RELEASE = 0x022;
    const CODE_UNSUPPORTED     = 0x100;
}
