<?php

namespace App;

function autoload($class)
{
    if (preg_match('/^App\\\\(.+)$/', $class, $matches)){
        require  __DIR__ . '/app/' . str_replace('\\', '/', $matches[1]) . '.php';;
    }
}
spl_autoload_register('App\autoload');

Application::run();
