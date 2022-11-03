#!/usr/bin/env php
<?php

define('ARCHIVE', 'idump.phar');

$stub = <<< STUB
#!/usr/bin/env php
<?php
    Phar::mapPhar("{ARCHIVE}");
    require "phar://{ARCHIVE}/bootstrap.php";
    __HALT_COMPILER();
STUB;

$phar = new Phar(ARCHIVE);
$phar->buildFromDirectory(__DIR__.'/src');
$phar->setStub($stub);
