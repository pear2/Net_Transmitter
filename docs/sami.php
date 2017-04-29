<?php

/**
 * File sami.php for PEAR2_Net_Transmitter.
 * 
 * PHP version 5.3
 * 
 * @category  Net
 * @package   PEAR2_Net_Transmitter
 * @author    Vasil Rangelov <boen.robot@gmail.com>
 * @copyright 2011 Vasil Rangelov
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @version   GIT: $Id$
 * @link      http://pear2.php.net/PEAR2_Net_Transmitter
 */

use Sami\Sami;
use Symfony\Component\Finder\Finder;

return new Sami(
    Finder::create()
        ->files()
        ->name('*.php')
        ->exclude('docs')
        ->exclude('tests')
        ->in($dir = dirname(__DIR__)),
    array(
        'title'                => 'PEAR2_Net_Transmitter documentation',
        'build_dir'            => __DIR__ . '/Reference/Sami/Doc',
        'cache_dir'            => __DIR__ . '/Reference/Sami/Cache',
        'default_opened_level' => 1
    )
);
