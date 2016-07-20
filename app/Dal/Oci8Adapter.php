<?php

namespace OraMap\Dal;

class Oci8Adapter implements DbAdapterInterface
{

    const REGULAR_CONNECTION = 0;
    const UNIQUE_CONNECTION = 1;
    const PERSISTENT_CONNECTION = 2;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var resource
     */
    protected $connection;

    /**
     * @var int
     */
    protected $fetchMode = OCI_ASSOC;

    /**
     * @var bool
     */
    private $prepared;

    /**
     * @var string
     */
    private $sql;

    /**
     * @var resource
     */
    protected $statement;


    /**
     * Oci8Adapter constructor.
     * @param string $username
     * @param string $password
     * @param string $dsn
     */
    public function __construct($username, $password, $dsn) {
        $this->config = compact('username', 'password', 'dsn');
    }

    /**
     * @param int $connection_type
     * @return resource
     */
    public function connect($connection_type = 0) {
        if($this->connection !== null) {
            return $this->connection;
        }
        else {
            if($connection_type == self::PERSISTENT_CONNECTION) {
                $this->connection = oci_pconnect($this->config['username'], $this->config['password'], $this->config['dsn']);
            }
            else if($connection_type == self::UNIQUE_CONNECTION) {
                $this->connection = oci_new_connect($this->config['username'], $this->config['password'], $this->config['dsn']);
            }
            else {
                $this->connection = oci_connect($this->config['username'], $this->config['password'], $this->config['dsn']);
            }
        }
        if(!$this->connection) {
            $e = oci_error();
            throw new \RuntimeException(htmlentities($e['message'], ENT_QUOTES));
        }
        return $this->connection;
    }

    /**
     * Resets the connection.
     */
    public function disconnect() {
        $this->connection = null;
    }

    /**
     * @param null $sql
     * @return $this
     */
    public function prepare($sql = null) {
        $this->connect();
        $sql = ($sql) ? : $this->sql;
        $this->statement = oci_parse($this->connection, $sql);
        $this->prepared = true;
        return $this;
    }

    /**
     * @param array $parameters
     * @param int $mode
     * @return $this|array
     */
    public function execute(array $parameters = array(), $mode = OCI_NO_AUTO_COMMIT) {
        if(!$this->prepared) {
            $this->prepare();
        }
        if($parameters) {
            $this->bind($parameters);
        }
        $result = oci_execute($this->statement, $mode);
        if(!$result) {
            return oci_error($this->statement);
        }
        return $this;
    }

    /**
     * @param array $parameters
     */
    private function bind(array $parameters = array()) {
        foreach($parameters as $key => $value) {
            if($value instanceof \DateTime) {
                oci_bind_by_name($this->statement, $key, $value->format("Y-m-d H:i:s"));
            }
            elseif(strtolower($key) != "id") {
                oci_bind_by_name($this->statement, $key, $parameters[$key]);
            }
        }
    }

    /**
     * Gets the affected rows from execution
     * @return int
     */
    public function countAffectedRows() {
        if(!$this->statement) {
            throw new \RuntimeException("$this->statement has not been set.");
        }
        return oci_num_rows($this->statement);
    }

    /**
     * @param string $table_name
     * @return int
     */
    public function getLastInsertId($table_name) {
        $this->sql = "select {$table_name}_seq.currval from dual";
        $this->execute();
        return oci_fetch_array($this->statement, OCI_ASSOC + OCI_RETURN_NULLS)[0];
    }

    /**
     * @param null|int $fetchStyle
     * @return array
     */
    public function fetch($fetchStyle = null) {
        if(!$this->statement) {
            throw new \RuntimeException("$this->statement has not been set.");
        }

        if($fetchStyle === null) {
            $fetchStyle = $this->fetchMode;
        }
        $results = array();

        while($row = oci_fetch_array($this->statement, $fetchStyle)) {
            $item = array();
            foreach($row as $key => $value) {
                $item[strtolower($key)] = $value;
            }
            $results[] = $item;
        }
        return $results;
    }

    /**
     * @param array $flags
     * @return mixed
     */
    public function fetchAll(array $flags = null) {
        if(!$this->statement) {
            throw new \RuntimeException("$this->statement has not been set.");
        }

        $mode = OCI_FETCHSTATEMENT_BY_ROW + OCI_ASSOC;

        if(is_array($flags) && count($flags) > 0) {
            $mode = 0;
            foreach($flags as $flag) {
                $mode = $mode + $flag;
            }
        }
        oci_fetch_all($this->statement, $result, 0, -1, $mode);
        return $result;
    }

    /**
     * @param string $table
     * @param array $bind
     * @param string $boolOperator
     * @return $this
     */
    public function select($table, array $bind = array(), $boolOperator = "AND") {
        $whereStr = "";
        if($bind) {
            $where = array();
            foreach($bind as $key => $value) {
                unset($bind[$key]);
                $bind[":" . $key . "_bv"] = $value;
                $where[] = $key . " = :" . $key . "_bv";
            }
            $whereStr = " WHERE " . implode(" " . $boolOperator . " ", $where);
        }

        $sql = "SELECT * FROM " . $table . $whereStr;
        $this->prepare($sql)->execute($bind);
        return $this;
    }

    /**
     * @param string $table
     * @param array $bind
     * @return int
     */
    public function insert($table, array $bind) {
        $columns = implode(", ", array_keys($bind));
        $values = implode(", :", array_keys($bind));
        foreach($bind as $key => $value) {
            unset($bind[$key]);
            $bind[":" . $key] = $value;
        }

        $sql = "INSERT INTO " . $table . " (" . $columns . ") VALUES(:" . $values . ")";

        return (int) $this->prepare($sql)
            ->execute($bind)
            ->getLastInsertId($table);
    }

    /**
     * @param string $table
     * @param array $bind
     * @param string $where
     * @return int
     */
    public function update($table, array $bind, $where = "") {
        $set = array();
        foreach($bind as $key => $value) {
            unset($bind[$key]);
            $bind[":" . $key . "_bv"] = $value;
            $set[] = $key . " = :" . $key . "_bv";
        }

        $sql = "UPDATE " . $table . " SET " . implode(", ", $set) . (($where) ? " WHERE" . $where : " ");
        return $this->prepare($sql)
            ->execute($bind)
            ->countAffectedRows();
    }

    /**
     * @param string $table
     * @param string $where
     * @return int
     */
    public function delete($table, $where = "") {
        $sql = "DELETE FROM " . $table . (($where) ? " WHERE " . $where : " ");
        return $this->prepare($sql)
            ->execute()
            ->countAffectedRows();
    }
}