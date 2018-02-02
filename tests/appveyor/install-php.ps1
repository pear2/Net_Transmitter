if (!(Test-Path $env:PHP_DIR)) {
    appveyor-retry cinst --params ('""/InstallDir:' + $env:PHP_DIR + '""') -y php --version (
        (
        choco search php --exact --all-versions -r |
        select-string -pattern ('\|' + [regex]::Escape($env:php_ver_target) + '(\D.*)?$') |
        Sort-Object {
            [version](
            $_ -split '\|' |
            Select-Object -last 1
            )
        } -Descending |
        Select-Object -first 1
        ) -replace '[php|]',''
    )
    Push-Location $env:PHP_DIR
    Copy-Item -Path .\php.ini-development -Destination .\php.ini -Force
    appveyor-retry appveyor DownloadFile $env:php_xdebug -FileName ($env:PHP_DIR + '\ext\php_xdebug.dll')
    $iniTail = "[XDebug]`nzend_extension=`"${env:PHP_DIR}\ext\php_xdebug.dll`"`n"
    if ((Test-Path env:php_uopz)) {
        appveyor-retry appveyor DownloadFile $env:php_uopz -FileName uopz.zip
        7z e uopz.zip "-i!php_uopz.dll" ('-o' + $env:PHP_DIR + '\ext')
        $iniTail += "[uopz]`nextension=php_uopz.dll`n"
    }
    (Get-Content .\php.ini) -replace
    ';date.timezone =', 'date.timezone = "UTC"' -replace
    '; extension_dir = "ext"', 'extension_dir = "ext"' -replace
    ';extension=php_openssl.dll', 'extension=php_openssl.dll' -replace
    ';extension=php_mbstring.dll', 'extension=php_mbstring.dll' -replace
    ';extension=php_soap.dll', 'extension=php_soap.dll' -replace
    ';extension=openssl', 'extension=openssl' -replace
    ';extension=mbstring', 'extension=mbstring' -replace
    ';extension=soap', 'extension=soap' -replace
    '; Local Variables:', ($iniTail + '; Local Variables:') | Set-Content .\php.ini
    appveyor-retry appveyor DownloadFile https://getcomposer.org/composer.phar
    '@php %~dpn0.phar %*' | Out-File .\composer.bat -Encoding ascii
    Pop-Location
}
$env:PATH = ($env:PHP_DIR + [IO.Path]::PathSeparator + $env:PATH)
appveyor SetVariable -Name 'PATH' -Value $env:PATH
