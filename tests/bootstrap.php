<?php

/**
 * bootstrap.php for PEAR2_Net_Transmitter.
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
 * Possible autoloader to initialize.
 */
use PEAR2\Autoload;

chdir(__DIR__);

$autoloader = stream_resolve_include_path('../vendor/autoload.php');
if (false !== $autoloader) {
    include_once $autoloader;
} else {
    $autoloader = stream_resolve_include_path('PEAR2/Autoload.php');
    if (false !== $autoloader) {
        include_once $autoloader;
        Autoload::initialize(realpath('../src'));
        Autoload::initialize(realpath('../../Cache_SHM.git/src'));
    } else {
        fwrite(STDERR, 'No recognized autoloader is available.');
        exit(1);
    }
}
unset($autoloader);

if (!is_file(__DIR__ . DIRECTORY_SEPARATOR . CERTIFICATE_FILE)) {
    //Prepare a self signed certificate
    $configargs = array();
    if (strpos(PHP_OS, 'WIN') === 0) {
        $phpbin = defined('PHP_BINARY')
            ? PHP_BINARY
            : getenv('PHP_PEAR_PHP_BIN');
        $configargs['config'] = dirname($phpbin) . '/extras/ssl/openssl.cnf';
    }

    $privkey = openssl_pkey_new($configargs);
    $cert = openssl_csr_sign(
        openssl_csr_new(
            array(
                'countryName' => 'US',
                'stateOrProvinceName' => 'IRRELEVANT',
                'localityName' => 'IRRELEVANT',
                'organizationName' => 'PEAR2',
                'organizationalUnitName' => 'PEAR2',
                'commonName' => 'IRRELEVANT',
                'emailAddress' => 'IRRELEVANT@example.com'
            ),
            $privkey,
            $configargs
        ),
        null,
        $privkey,
        2,
        $configargs
    );

    $pem = array();
    openssl_x509_export($cert, $pem[0]);
    openssl_pkey_export($privkey, $pem[1], null, $configargs);

    file_put_contents(
        __DIR__ . DIRECTORY_SEPARATOR . CERTIFICATE_FILE,
        implode('', $pem)
    );
}