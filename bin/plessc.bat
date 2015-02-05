@ECHO OFF
SET BIN_TARGET=%~dp0/../vendor/leafo/lessphp/plessc
php "%BIN_TARGET%" %*
