<?php

namespace App;

use Phar;
use App\Exceptions\UsageException;

class Environment
{
    /**
     * instance of singleton
     *
     * @var self
     */
    private static $instance = null;

    /**
     * argv[0]
     *
     * @var string
     */
    public $script;

    /**
     * working directory
     *
     * @var string
     */
    public $workpath;

    /**
     * aliases of options
     *
     * @var array
     */
    private $aliases = [
        'h' => 'host',
        'P' => 'port',
        'u' => 'username',
        'p' => 'password',
    ];

    /**
     * option values
     *
     * @var array
     */
    private $options = [
        'host'          => 'localhost',
        'port'          => null,
        'database'      => null,
        'username'      => null,
        'password'      => null,
        'charset'       => 'utf8mb4',
        'sort-indexes'  => false,
        'no-increment'  => false,
        'dist'          => null,
    ];

    /**
     * arguments of command
     *
     * @var array
     */
    private $arguments = [];


    /**
     * get instance of singleton
     *
     * @return self
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self;
            self::$instance->initialize();
        }
        return self::$instance;
    }

    /**
     * get the value of option
     *
     * @return mixed
     */
    public function option($name)
    {
        if (array_key_exists($name, $this->options)) {
            return $this->options[$name];
        }
        return null;
    }

    /**
     * initialize environment of command
     *
     * @return void
     */
    private function initialize()
    {
        global $argv;

        if (preg_match('/^phar\:\/\//', __FILE__)) {
            $this->workpath = dirname(Phar::running(false));
        } else {
            $this->workpath = dirname(dirname(dirname(__FILE__)));
        }

        $this->script = array_shift($argv);

        $alias = null;
        foreach ($argv as $part) {
            if (!is_null($alias)) {
                $this->options[$this->aliases[$matches[1]]] = $part;
                $alias = null;
            } else if (preg_match('/^\-\-([^=].*)=(.+)$/', $part, $matches)) {
                if (!array_key_exists($matches[1], $this->options)) {
                    throw new UsageException("[ERROR] unknown option '--{$matches[1]}'");
                }
                $this->options[$matches[1]] = $matches[2];
            } else if (preg_match('/^\-\-(.+)$/', $part, $matches)) {
                if (!array_key_exists($matches[1], $this->options)) {
                    throw new UsageException("[ERROR] unknown option '--{$matches[1]}'");
                }
                $this->options[$matches[1]] = true;
            } else if (preg_match('/^\-([^\-].*)$/', $part, $matches)) {
                if (!array_key_exists($matches[1], $this->aliases)) {
                    throw new UsageException("[ERROR] unknown option '{$matches[0]}'");
                }
                $alias = $matches[1];
            } else {
                $this->arguments[] = $part;
            }
        }
        if (!is_null($alias)) {
            throw new UsageException("[ERROR] option '-{$alias}' requires an argument");
        }

        $this->options['database'] = array_shift($this->arguments);
        if (empty($this->options['database'])) {
            throw new UsageException();
        }

        if (empty($this->options['dist'])) {
            $this->options['dist'] = "dist/{$this->options['database']}";
        }
    }
}
