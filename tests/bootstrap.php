<?php
if (is_file('../vendor/autoload.php')) {
    include_once '../vendor/autoload.php';
} elseif (is_file('PEAR2/Autoload.php')) {
    include_once 'PEAR2/Autoload.php';
    PEAR2\Autoload::initialize('../src');
    PEAR2\Autoload::initialize('../../Cache_SHM.git/src');
} else {
    die('No recognized autoloader is available.');
}
