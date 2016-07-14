<?php

namespace OraMap\Dal;

interface IOracleGateway extends IGateway
{
    function setTable($tableName);
    function getTable();
    function query($sql, $assoc = true);
    function lastSequenceValue();
}