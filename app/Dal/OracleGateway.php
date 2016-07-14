<?php

namespace OraMap\Dal;
use OraMap\Models\SqlRecord;
use Libraries\Logger;

class OracleGateway implements IOracleGateway
{
    private static $handle;

    private $logger;
    private $table;
    private $statement;

    function __construct($handle = null, $tableName = null, $logger = false) {

        if(!isset($_SESSION)) {
            session_start();
        }

        self::$handle = $handle;

        if(is_null(self::$handle)) {
            $username = $_SESSION['DBUSER'];
            $pass = $_SESSION['DBPASS'];
            $dsn = $_SESSION['DBDSN'];
            self::$handle = oci_connect($username, $pass, $dsn);
        }

        if($logger)
        {
            $this->logger = Logger::getInstance();
        }
    }

    /**
     * closes the oci connection
     */
    public function __destruct() {
        if(isset($this->statement))
        {
            oci_free_statement($this->statement);
        }
        oci_close(self::$handle);
    }

    /**
     * Deletes a record in oracle by it's ID
     * @param $id - the id of the record in oracle
     * @return array|bool - array if an oracle error is created, otherwise true
     */
    public function delete($id)
    {
        $id = strtolower($id);
        $sql = "delete from {$this->getTable()} where lower(id) = lower('{$id}')";

        if(!is_null($this->logger))
        {
            $this->logger->log('DEBUG', $sql);
        }

        $this->statement = oci_parse(self::$handle, $sql);
        $result =  oci_execute($this->statement);
        if($result)
        {
            return $result;
        }
        else
        {
            return oci_error($this->statement);
        }
    }

    /**
     * Deletes a record in oracle using a key value pair. This assumes that the key is not a date column.
     * If you wish to delete by a specific date, the value of the parameter must be a string that is formatted
     * so that it can be read by Oracle.
     *
     * Eg. array(
     *              "extract(day) from added_dt" => "6",
     *              "extract(month) from added_dt" => "4"
     *          )
     *
     *
     * @param array $keyValuePair - the key is the column name the value is the column value
     * @param string $operator defaults to "=" but can be set to another operator that oracle recognizes
     * @return array|bool
     */
    public function deleteWhere(array $keyValuePair, $operator = "=")
    {
        $whereClause = "";

        foreach($keyValuePair as $key => $value)
        {
            $whereClause .= "lower({$key}) {$operator} lower('{$value}') AND";
        }

        $whereClause = rtrim($whereClause, 'AND');

        $sql = "delete from {$this->getTable()} where {$whereClause}";

        if(!is_null($this->logger)) {
            $this->logger->log('DEBUG', $sql);
        }

        $this->statement = oci_parse(self::$handle, $sql);
        $result =  oci_execute($this->statement);
        if($result)
        {
            return $result;
        }
        else
        {
            return oci_error($this->statement);
        }
    }

    /**
     * executes a create or drop table query
     *
     * @param string $query the sql query for creating or dropping a table
     * @return true on success, oci_error on fail
     */
    public function exec($query)
    {
        $statement = oci_parse(self::$handle, $query);
        if(oci_execute($statement) !== true)
        {
            $e = oci_error($statement);
            trigger_error(htmlentities($e['message']), E_USER_ERROR);
        }
    }

    /**
     * Find's a single record in oracle that matches the given ID. Since ID's are auto generated from oracle, most requests will be made
     * using this objects findByKeyValue method
     * @param $id string
     * @return array
     * @throws \Exception on OCI Error
     */
    public function find($id)
    {
        $sql = "select * from {$this->table} where lower(id) = lower('{$id}')";

        return $this->query($sql);
    }

    /**
     * Queries oracle for all records that match the key-value pair, where the key is the column name, and the value is that
     * columns value
     *
     * @param $key - the column name in oracle
     * @param $value - the column value in oracle
     * @return array - all fields of the record in oracle as an associative array
     * @throws \Exception
     */
    public function findByKeyValue($key, $value)
    {
        $sql = "select * from {$this->getTable()} where lower({$key}) = lower('{$value}')";

        if(!is_null($this->logger)) {
            $this->logger->log('DEBUG', $sql);
        }

        return $this->query($sql);
    }

    /**
     * Queries oracle for all record that match ALL key-value pairs, where the key is the column name, and the value is that
     * columns value. Only works on varchar and number columns. Use query function for date columns. Use findByAnyKeyValue for OR clauses
     * @param array $keyValuePair
     * @param string $condition - default to and, can be set to or as well. See self::findByAnyKeyValue()
     * @return array - results as associative array
     * @throws \Exception
     */
    public function findByAllKeyValue(array $keyValuePair, $condition = 'and')
    {
        $whereClause = "where ";
        foreach($keyValuePair as $key => $value)
        {
            $whereClause .= "lower{$key} = lower('{$value}') $condition ";
        }
        $whereClause = rtrim($whereClause, "{$condition} ");
        $sql = "select * from {$this->getTable()} {$whereClause}";

        if(!is_null($this->logger)) {
            $this->logger->log('DEBUG', $sql);
        }

        return $this->query($sql);
    }

    /**
     * Queries oracle for all record that match ANY key-value pairs, where the key is the column name, and the value is that
     * columns value. Only works on varchar and number columns. Use query function for date columns. Use findByAnyKeyValue for OR clauses
     * @param array $keyValuePair
     * @return array
     */
    public function findByAnyKeyValue(array $keyValuePair)
    {
        return $this->findByAllKeyValue($keyValuePair, 'or');
    }

    /**
     * Confirms that a connection is established by returning version info
     * @return string
     */
    public function getConnection() {
        return oci_server_version(self::$handle);
    }

    /**
     * Gets the currently set table name. If no table name is set explicitly, this returns the default table name used in
     * this objects constructor
     * @return mixed - the table name
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Inserts a new record into oracle. The id field in SqlRecord is not necessary since ID's are auto assigned using sequences
     * from oracle
     * @param SqlRecord $record
     * @return array|bool - array if an oracle error occurs, otherwise true
     * @throws \Exception
     */
    public function insert(SqlRecord $record)
    {
        $insertString = str_replace('seq', $this->table . "_seq.nextval", $record->getInsertString());

        $sql = "insert into {$this->table} {$insertString}";

        if(!is_null($this->logger)) {
            $this->logger->log('DEBUG', $sql);
        }

        $this->statement = oci_parse(self::$handle, $sql);
        $recordArray = $record->getRecord();

        foreach($recordArray as $field => $value) {
            if ($value instanceof \DateTime)
            {
                oci_bind_by_name($this->statement, ":".$field."_bv", $value->format('Y-m-d H:i:s'));
            }
            elseif(strtolower($field) != 'id')
            {
                oci_bind_by_name($this->statement, ":".$field."_bv", $recordArray[$field]);
            }
        }
        try {
            if(!oci_execute($this->statement))
            {
                return oci_error($this->statement);
            }
            return true;
        }
        catch(\Exception $e)
        {
            throw new \Exception('Failed to insert record. Problem  executing the following query: {$query}', oci_error($this->statement));
        }
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function lastSequenceValue() {
        $sql = "select last_number as id from all_sequences where lower(sequence_name) = '{$this->getTable()}_seq'";
        return $this->query($sql);
    }

    /**
     * performs a query statement on the database that returns one or more rows
     *
     * @param $sql string the sql text to parse
     * @param $assoc bool function return associative array if true [default]
     * @return array of results if execution succeeds
     * @throws \Exception on OCI error
     *
     */
    public function query($sql, $assoc = true)
    {
        $this->statement = oci_parse(self::$handle, $sql);

        if(!is_null($this->logger)) {
            $this->logger->log('DEBUG', $sql);
        }

        try{
            if(oci_execute($this->statement))
            {
                $results = array();

                if($assoc)
                {
                    while(($row = oci_fetch_array($this->statement, OCI_ASSOC + OCI_RETURN_NULLS)) != false)
                    {
                        $item = array();
                        foreach($row as $key => $value)
                        {
                            $item[strtolower($key)] = $value;
                        }
                        $results[] = $item;
                    }
                }
                else
                {
                    while(($row = oci_fetch_array($this->statement, OCI_RETURN_NULLS)) != false)
                    {
                        $results[] = $row;
                    }
                }
                return $results;
            }
            else
            {
                return oci_error($this->statement);
            }
        }
        catch(\Exception $e)
        {
            throw new \Exception('Failed to fetch record. Problem executing the following query: {$query}', oci_error($this->statement));
        }
    }

    /**
     * Explicitly sets the table name
     * @param $tableName string - the desired table name to perform oracle queries on
     * @return void
     */
    public function setTable($tableName)
    {
        $this->table = $tableName;
    }

    /**
     * Updates a record in oracle. An ID is needed from the SqlRecord to update. This is usually done by performing $this->findByKeyValue()
     * to get the ID, which is used to create a new SqlRecord
     *
     * @param SqlRecord $record
     * @return array|bool - array if an oracle error is created, otherwise true
     */
    public function update(SqlRecord $record)
    {

        $updateString = $record->getUpdateString();
        $recordId = strtolower($record->getField('id'));
        $sql = "update {$this->getTable()} set {$updateString} where lower(id) = '{$recordId}'";

        if(!is_null($this->logger)) {
            $this->logger->log('DEBUG', $sql);
        }

        $this->statement = oci_parse(self::$handle, $sql);

        $recordArray = $record->getRecord();

        foreach($recordArray as $field => $value) {
            if ($value instanceof \DateTime)
            {
                oci_bind_by_name($this->statement, ":".$field."_bv", $value->format('Y-m-d H:i:s'));
            }
            elseif(strtolower($field) != 'id')
            {
                oci_bind_by_name($this->statement, ":".$field."_bv", $recordArray[$field]);
            }
        }

        $result = oci_execute($this->statement);
        if($result)
        {
            return $result;
        }
        else
        {
            return oci_error($this->statement);
        }
    }
}