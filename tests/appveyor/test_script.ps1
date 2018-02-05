$env:PHP_PEAR_PHP_BIN=($env:PHP_DIR + '\php.exe')

Push-Location .\tests

$secondaryPeer = (.\secondaryPeer.bat --coverage-clover=cs.clover &)
Wait-Job $secondaryPeer -Timeout 3
phpunit --coverage-clover=cf.clover

Wait-Job $secondaryPeer
Receive-Job $secondaryPeer

ocular code-coverage:upload --format=php-clover cf.clover
ocular code-coverage:upload --format=php-clover cs.clover

Pop-Location
