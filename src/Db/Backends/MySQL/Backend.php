<?php

namespace Phlite\Db\Backends\MySQL;

use Phlite\Db;

class Backend extends Db\Backend {
    
    static $compiler = __NAMESPACE__ . '\Compiler';
    static $executor = __NAMESPACE__ . '\Executor';
    
    var $info;
    
    function __construct(array $info) {
        $this->info = $info;
    }
    
    function getCompiler($options) {
       $class = static::$compiler;
       return new $class($this, $options);
    }
    
    function getExecutor(Statement $stmt) {
        $class = static::$executor;
        return new $class($stmt);
    }
    
    function getConnection() {
        $this->connect();
        return $this->conn;
    }
    
    function connect() {
        if ($this->conn)
            // No auto reconnect, use ::disconnect() first
            return;
        
        $user = $this->info['USER'];
        $passwd = $this->info['PASSWORD'];
        $host = $this->info['HOST'];
        $options = $this->info['OPTIONS'];
        
        // Assertions
        if(!strlen($user) || !strlen($host))
            throw new \Exception('Database settings are missing USER and HOST settings');

        if (!($this->conn = mysqli_init()))
            throw new \Exception('MySQLi extension is missing on this system');

        // Setup SSL if enabled
        if (isset($options['ssl']))
            $this->conn->ssl_set(
                    $options['ssl']['key'],
                    $options['ssl']['cert'],
                    $options['ssl']['ca'],
                    null, null);

        $port = ini_get("mysqli.default_port");
        $socket = ini_get("mysqli.default_socket");
        $persistent = stripos($host, 'p:') === 0;
        if ($persistent)
            $host = substr($host, 2);
        if (strpos($host, ':') !== false) {
            list($host, $portspec) = explode(':', $host);
            // PHP may not honor the port number if connecting to 'localhost'
            if ($portspec && is_numeric($portspec)) {
                if (!strcasecmp($host, 'localhost'))
                    // XXX: Looks like PHP gethostbyname() is IPv4 only
                    $host = gethostbyname($host);
                $port = (int) $portspec;
            }
            elseif ($portspec) {
                $socket = $portspec;
            }
        }

        if ($persistent)
            $host = 'p:' . $host;

        // Connect
        if (!@$this->conn->real_connect($host, $user, $passwd, null, $port, $socket))
            throw new \Exception(sprintf(
                'Unable to connect to MySQL database at %s@%s using supplied credentials'
                $user, $host));
        
        //Select the database, if any.
        if (isset($options['DATABASE']))
            $this->conn->select_db($options['DATABASE']);

        //set desired encoding just in case mysql charset is not UTF-8 - Thanks to FreshMedia
        @$this->conn->query('SET NAMES "utf8"');
        @$this->conn->query('SET CHARACTER SET "utf8"');
        @$this->conn->query('SET COLLATION_CONNECTION=utf8_general_ci');
        $this->conn->set_charset('utf8');

        @db_set_variable('sql_mode', '');

        // Start a new transaction -- disable autocommit
        if (isset($this->info['OPTIONS']['AUTOCOMMIT']))
            $this->conn->autocommit($this->info['OPTIONS']['AUTOCOMMIT']);
    }
}