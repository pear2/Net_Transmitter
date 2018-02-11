if (!(Test-Path $env:PHP_COMPOSER_DIR)) {
    mkdir $env:PHP_COMPOSER_DIR
    Push-Location $env:PHP_COMPOSER_DIR
    appveyor-retry appveyor DownloadFile https://composer.github.io/installer.sig
    appveyor-retry appveyor DownloadFile https://getcomposer.org/installer
    if ((Get-Content .\installer.sig) -ne (Get-FileHash .\installer -Algorithm SHA384).Hash) {
        Write-Error 'Composer installer signature mismatch.'
        exit 1;
    }
    php installer
    Remove-Item .\installer
    Remove-Item .\installer.sig
    '@php %~dpn0.phar %*' | Out-File .\composer.bat -Encoding ascii
    Pop-Location
}
$env:PATH = ($env:PHP_COMPOSER_DIR + [IO.Path]::PathSeparator + $env:PATH)
appveyor SetVariable -Name 'PATH' -Value $env:PATH

composer self-update --no-progress

Push-Location ($env:APPDATA + '\Composer')
if ((Test-Path .\vendor\bin)) {
    $env:PATH = ((Resolve-Path .\vendor\bin).ToString() + [IO.Path]::PathSeparator + $env:PATH)
    appveyor SetVariable -Name 'PATH' -Value $env:PATH
}
Pop-Location
