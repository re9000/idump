<?php
namespace App;

use PDO;

class DB
{
    /**
     * instance of singleton
     *
     * @var self
     */
    private static $instance = null;

    /**
     * instance of PDO object
     *
     * @var PDO
     */
    private $pdo;

    /**
     * gets instance of this class.
     *
     * @return self
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * @return void
     */
    private function __construct()
    {
    }

    /**
     * creates instance of this class, and connects to database server.
     *
     * @return void
     */
    public static function initialize($username, $password)
    {
        $me = self::getInstance();
        $me->connect($username, $password);
    }

    /**
     * connects to database server.
     *
     * @param  string $username
     * @param  string $password
     * @return void
     */
    public function connect($username, $password)
    {
        $env = Environment::getInstance();

        $dsn = 'mysql:';

        $dsn .= "host={$env->option('host')};";

        $port = $env->option('port');
        if (!empty($port)) {
            $dsn .= "port={$port};";
        }

        $dsn .= "dbname={$env->option('database')};";

        $charset = $env->option('charset');
        if (!empty($charset)) {
            $dsn .= "charset={$charset};";
        }

        $this->pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }

    /**
     * executes query, and gets records using a callback.
     *
     * @param  string   $sql
     * @param  callable $callback
     * @return void
     */
    public function query($sql, $callback)
    {
        $statement = $this->pdo->query($sql);
        while ($record = $statement->fetch(PDO::FETCH_OBJ)) {
            $callback($record);
        }
    }

    /**
     * gets the list of tables.
     *
     * @return array
     */
    public function getTableNames()
    {
        $tables = [];
        $statement = $this->pdo->query("show tables");
        while ($row = $statement->fetch(PDO::FETCH_NUM)) {
            $tables[] = reset($row);
        }
        return $tables;
    }

    /**
     * gets the statement of table creation.
     *
     * @param   string $name
     * @return  string
     */
    public function generateTableCreation($name)
    {
        $env = Environment::getInstance();

        $statement = $this->pdo->query("show create table {$name}");
        $content = $statement->fetch(PDO::FETCH_NUM)[1];
        $lines = explode("\n", $content);

        $result = '';
        $after = '';
        $found = false;
        $indexes = [];
        foreach ($lines as $line) {
            $line = rtrim($line);
            if (preg_match('/^\s+(\w+\s+)?KEY /', $line, $matches)) {
                $found = true;
                if (preg_match('/PRIMARY\s+KEY/', $line)) {
                    $result .= $line . "\n";
                } else {
                    $index = rtrim($line, ',');
                    if (preg_match('/KEY\s+`(.+)`\s+\(/', $index, $matches)) {
                        $indexes[$matches[1]] = $index;
                    }
                }
            } else {
                if ($found) {
                    $after .= $line . "\n";
                } else {
                    $result .= $line . "\n";
                }
            }
        }
        if (!empty($indexes)) {
            if ($env->option('sort-indexes')) {
                ksort($indexes);
            }
            $result .= implode(",\n", $indexes) . "\n";
        }
        $result .= $after;

        if ($env->option('no-increment')) {
            $result = preg_replace('/AUTO_INCREMENT=[0-9]+\s/', '', $result); 
        }

        return $result;
    }
}
