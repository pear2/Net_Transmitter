<?php
$extrafiles = array();

foreach (
    array(
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'PEAR2_Cache_SHM.git'
    ) as $packageRoot
) {
    $pkg = new \Pyrus\Package(
        $packageRoot . DIRECTORY_SEPARATOR . 'package.xml'
    );
    foreach (array('tests', 'docs') as $folder) {
        foreach (
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $packageRoot . DIRECTORY_SEPARATOR . $folder,
                    RecursiveDirectoryIterator::UNIX_PATHS
                ),
                RecursiveIteratorIterator::LEAVES_ONLY
            ) as $path
        ) {
            unset($pkg->files[$path->getPathname()]);
        }
    }
    $extrafiles[] = $pkg;
}