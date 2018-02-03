if (!(Test-Path $env:PHP_DIR)) {
    appveyor-retry cinst php -y --params ('""/InstallDir:' + $env:PHP_DIR + '""') --version (
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
    $iniTail = "[curl]`ncurl.cainfo=`"C:\usr\local\ssl\cert.pem`"`n"
    if ((Test-Path env:php_xdebug)) {
        appveyor-retry appveyor DownloadFile $env:php_xdebug -FileName ($env:PHP_DIR + '\ext\php_xdebug.dll')
        $iniTail += "[XDebug]`nzend_extension=`"${env:PHP_DIR}\ext\php_xdebug.dll`"`n"
    }
    if ((Test-Path env:php_uopz)) {
        appveyor-retry appveyor DownloadFile $env:php_uopz -FileName uopz.zip
        7z e uopz.zip "-i!php_uopz.dll" ('-o.\ext')
        $iniTail += "[uopz]`nextension=php_uopz.dll`n"
    }
    (Get-Content .\php.ini) -replace
    ';date.timezone =', 'date.timezone = "UTC"' -replace
    '; extension_dir = "ext"', 'extension_dir = "ext"' -replace
    ';extension=php_curl.dll', 'extension=php_curl.dll' -replace
    ';extension=php_openssl.dll', 'extension=php_openssl.dll' -replace
    ';extension=php_mbstring.dll', 'extension=php_mbstring.dll' -replace
    ';extension=php_soap.dll', 'extension=php_soap.dll' -replace
    ';extension=curl', 'extension=curl' -replace
    ';extension=openssl', 'extension=openssl' -replace
    ';extension=mbstring', 'extension=mbstring' -replace
    ';extension=soap', 'extension=soap' -replace
    '; Local Variables:', ($iniTail + '; Local Variables:') | Set-Content .\php.ini
    netsh advfirewall firewall add rule dir=out action=allow name=php program=((Resolve-Path .\php.exe).ToString())
    Pop-Location
}
$env:PATH = ($env:PHP_DIR + [IO.Path]::PathSeparator + $env:PATH)
appveyor SetVariable -Name 'PATH' -Value $env:PATH
