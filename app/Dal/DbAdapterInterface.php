<?php

namespace OraMap\Dal;

interface DbAdapterInterface
{
    function connect();
    function disconnect();
    function prepare($sql);
    function execute(array $params, $mode = OCI_NO_AUTO_COMMIT);
    function fetch($fetchStyle = null);
    function fetchAll(array $flags = null);
    function insert($table, array $bind);
    function update($table, array $bind, $where = "");
    function delete($table, $where = "");
    function select($table, array $bind, $boolOperator = "AND");
}