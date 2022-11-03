<?php

namespace App;

use App\Exceptions\UsageException;
use Exception;

class Application
{
    /**
     * executes this application.
     *
     * @return void
     */
    public static function run()
    {
        $me = new self;
        $me->handle();
    }

    /**
     * @return void
     */
    private function __construct()
    {
    }

    /**
     * generates the filename of table creation.
     * @param  string $name
     * @return string
     */
    private function generateCreationName($name)
    {
        return implode('/', [
            Environment::getInstance()->workpath,
            Environment::getInstance()->option('dist'),
            'tables',
            "{$name}.sql",
        ]);
    }

    /**
     * generates the filename of dumped data.
     * @param  string $name
     * @return string
     */
    private function generateDumpName($name)
    {
        return implode('/', [
            Environment::getInstance()->workpath,
            Environment::getInstance()->option('dist'),
            'data',
            "{$name}.txt",
        ]);
    }

    /**
     * outputs the table creation.
     * @param  string $name
     * @return void
     */
    private function outputCreation($name)
    {
        $filename = $this->generateCreationName($name);

        Storage::preparePath($filename);
        Storage::putContent($filename, DB::getInstance()->generateTableCreation($name));
    }

    /**
     * outputs the dumped data.
     * @param  string $name
     * @return void
     */
    private function dump($name)
    {
        $filename = $this->generateDumpName($name);
        Storage::preparePath($filename);

        DB::getInstance()->query("select * from {$name}", function($record) use ($filename) {
            $content = print_r($record, true);
            $content = trim(preg_replace('/^stdClass Object/', '', $content)) . "\n";
            Storage::putContent($filename, $content, FILE_APPEND);
        });
    }

    /**
     * detetes files of dropped tables.
     * @param  array $names
     * @return void
     */
    private function mergeDroppedTables($names)
    {
        $keys = [];
        foreach ($names as $name) {
            $keys[$name] = true;
        }

        $files = glob($this->generateCreationName('*'));
        foreach ($files as $file) {
            if (!preg_match('/^(.+)\.sql$/', basename($file), $matches)) {
                continue;
            }
            $table = $matches[1];
            if (!array_key_exists($table, $keys)) {
                Storage::remove($this->generateCreationName($table));
            }
        }

        $files = glob($this->generateDumpName('*'));
        foreach ($files as $file) {
            if (!preg_match('/^(.+)\.txt$/', basename($file), $matches)) {
                continue;
            }
            $table = $matches[1];
            if (!array_key_exists($table, $keys)) {
                Storage::remove($this->generateDumpName($table));
            }
        }
    }

    /**
     * executes this command.
     *
     * @return void
     */
    private function handle()
    {
        try {
            $env = Environment::getInstance();

            $username = $env->option('username');
            if (empty($username)) {
                $username = Console::prompt('Enter username: ');
            }

            $password = $env->option('password');
            if (empty($password)) {
                $password = Console::prompt('Enter password: ', false);
            }

            $db = DB::getInstance();
            $db->connect($username, $password);

            $names = $db->getTableNames();

            $this->mergeDroppedTables($names);
            foreach ($names as $name) {
                $this->outputCreation($name);
                $this->dump($name);
            }

        } catch (UsageException $e) {
            Console::echo("\n");
            $message = $e->getMessage();
            if (!empty($message)) {
                Console::echo($message . "\n\n");
            }
            Console::showUsage();
        } catch (Exception $e) {
            Console::echo("\n");
            Console::echo('Caught Exception (' . get_class($e) . ")\n");
            Console::echo($e->getMessage() . "\n\n");
        }
    }
}
