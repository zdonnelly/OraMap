<?php 

namespace OraMap\Models;

interface SqlRecordInterface extends IRecord
{
    abstract function addField($key, $value);
    abstract function getField($key);
    abstract function getRecord();
    abstract function setField($key, $value);

    function getUpdateString();
    function getInsertString();

}