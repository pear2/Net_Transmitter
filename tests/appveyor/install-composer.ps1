composer self-update --no-progress

Push-Location ($env:APPDATA + '\Composer')
if ((Test-Path .\vendor\bin)) {
    $env:PATH = ((Resolve-Path .\vendor\bin).ToString() + [IO.Path]::PathSeparator + $env:PATH)
    appveyor SetVariable -Name 'PATH' -Value $env:PATH
}
Pop-Location
