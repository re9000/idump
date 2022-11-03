<?php

namespace App;

class Console
{
    /**
     * reads a line with prompt.
     *
     * @param  string $message
     * @param  bool   $echoback
     * @return string
     */
    public static function prompt($message, $echoback = true)
    {
        echo $message;
        $stty = shell_exec('stty -g');

        $command = 'stty ' . ($echoback ? 'echo' : '-echo');
        shell_exec($command);
        $input = rtrim(fgets(STDIN), "\n");

        if(!$echoback) {
            echo "\n";
        }

        shell_exec('stty ' . $stty);
        return $input;
    }

    /**
     * outputs a string.
     *
     * @param  string $text
     * @return void
     */
    public static function echo($text)
    {
        echo $text;
    }

    /**
     * outputs the usage of this command.
     *
     * @param  string $text
     * @return void
     */
    public static function showUsage()
    {
        echo "Usage: idump [OPTIONS] database\n";
        echo "  -h, --host=name         Connect to host.\n";
        echo "  -P, --port=#            Port number to use for connection.\n";
        echo "  -u, --user=name         User for login.\n";
        echo "  -p, --password=name     Password to use when connecting to server.\n";
        echo "\n";
    }
}
