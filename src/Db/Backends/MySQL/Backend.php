<?php

namespace Phlite\Db\Backends\MySQL;

use Phlite\Db;

class Backend extends Db\Backend {
    
    static $compiler =  'Phlite\Db\Backends\MySQL\Compiler';
    static $executor =  'Phlite\Db\Backends\MySQL\MySQLiExecutor';
        
    var $info;
    var $conn;
    
    function __construct(array $info) {
        $this->info = $info;
    }
    
    function getCompiler($options=false) {
       $class = static::$compiler;
       return new $class($this, $options);
    }
    
    function getExecutor(Db\Compile\Statement $stmt) {
        $class = static::$executor;
        return new $class($stmt, $this);
    }
    
    function getConnection() {
        $this->connect();
        return $this->conn;
    }
    
    function connect() {
        if (isset($this->conn))
            // No auto reconnect, use ::disconnect() first
            return;
        
        $user = $this->info['USER'];
        $passwd = $this->info['PASSWORD'];
        $host = $this->info['HOST'];
        $options = @$this->info['OPTIONS'] ?: array();
        
        // Assertions
        if(!strlen($user) || !strlen($host))
            throw new \Exception('Database settings are missing USER and HOST settings');

        if (!($this->conn = mysqli_init()))
            throw new \Exception('MySQLi extension is missing on this system');

        // Setup SSL if enabled
        if (isset($options['SSL']))
            $this->conn->ssl_set(
                    $options['SSL']['key'],
                    $options['SSL']['cert'],
                    $options['SSL']['ca'],
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
            throw new Db\Exception\ConnectionError(sprintf(
                'Unable to connect to MySQL database %s@%s using supplied credentials',
                $user, $host));
        
        //Select the database, if any.
        if (isset($this->info['NAME'])) {
            if (!$this->conn->select_db($this->info['NAME']))
                throw new Db\Exception\ConnectionError(sprintf(
                '%s: Unable to select database', $this->info['NAME']
                ));
        }

        //set desired encoding just in case mysql charset is not UTF-8 - Thanks to FreshMedia
        $this->set_variable('NAMES', 'utf8');
        $this->set_variable('CHARACTER SET', 'utf8');
        $this->set_variable('COLLATION_CONNECTION', 'utf8_general_ci');
        $this->conn->set_charset('utf8');
        $this->set_variable('sql_mode', '');

        // Start a new transaction -- disable autocommit
        if (isset($this->info['OPTIONS']['autocommit']))
            $this->conn->autocommit($this->info['OPTIONS']['autocommit']);
    }
    
    function get_variable($variable, $type='session') {
        $sql = sprintf('SELECT @@%s.%s', $type, $variable);
        return db_result(db_query($sql));
    }

    function set_variable($variable, $value, $type='session') {
        $sql = sprintf('SET %s %s=%s', strtoupper($type), $variable, $this->escape($value));
        return $this->conn->query($sql);
    }
    
    function escape($what) {
        return $this->conn->escape_string($what);
    }
}