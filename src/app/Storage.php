<?php

namespace App;

use Exception;

class Storage
{
    /**
     * attempts to create the directory specified by directory.
     * any parent directories to the directory specified will also be created, with the same permissions.
     *
     * @param  string $filename
     * @param  int    $permissions
     * @return void
     */
    public static function preparePath($filename, $permissions=0755)
    {
        $path = dirname($filename);
        if (!file_exists($path)) {
            $result = @mkdir($path, $permissions, true);
            if ($result === false) {
                throw new Exception("[ERROR] failed to mkdir ({$path})");
            }
        } else {
            if (!is_dir($path)) {
                throw new Exception("[ERROR] failed to prepare path ({$path})");
            }
        }
    }

    /**
     * writes data to a file.
     *
     * @param  string $filename
     * @param  int    $permissions
     * @return void
     */
    public static function putContent($filename, $content, $flags=null)
    {
        $result = @file_put_contents($filename, $content, $flags);
        if ($result === false) {
            throw new Exception("[ERROR] failed to write ({$filename})");
        }
    }

    /**
     * deletes a file.
     *
     * @param  string $filename
     * @param  int    $permissions
     * @return void
     */
    public static function remove($filename)
    {
        $result = @unlink($filename);
        if ($result === false) {
            throw new Exception("[ERROR] failed to remove ({$filename})");
        }
    }
}
