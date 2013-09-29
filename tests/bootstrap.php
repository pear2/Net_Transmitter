<?php
$autoloader = stream_resolve_include_path('/../vendor/autoload.php');
if (false !== $autoloader) {
    include_once $autoloader;
} else {
    $autoloader = stream_resolve_include_path('PEAR2/Autoload.php');
    if (false !== $autoloader) {
        include_once $autoloader;
        \PEAR2\Autoload::initialize('../src');
        \PEAR2\Autoload::initialize('../../Cache_SHM.git/src');
    } else {
        die('No recognized autoloader is available.');
    }
}
unset($autoloader);