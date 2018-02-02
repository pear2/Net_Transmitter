$env:PHP_PEAR_PHP_BIN=($env:PHP_DIR + '\php.exe')
$env:PHP_TESTS_FOLDER=($env:APPVEYOR_BUILD_FOLDER + '\tests')

Push-Location $env:PHP_TESTS_FOLDER
$secondaryPeer = (.\secondaryPeer.bat &)
Wait-Job $secondaryPeer -Timeout 3
phpunit
Pop-Location

Wait-Job $secondaryPeer
Receive-Job $secondaryPeer
