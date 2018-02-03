$env:PHP_PEAR_PHP_BIN=($env:PHP_DIR + '\php.exe')

Push-Location .\tests

$secondaryPeer = (.\secondaryPeer.bat --coverage-clover=cov_second.clover &)
Wait-Job $secondaryPeer -Timeout 3
phpunit --coverage-clover=coverage.clover

Wait-Job $secondaryPeer
Receive-Job $secondaryPeer
ocular code-coverage:upload --format=php-clover coverage.clover
ocular code-coverage:upload --format=php-clover cov_second.clover

Pop-Location
