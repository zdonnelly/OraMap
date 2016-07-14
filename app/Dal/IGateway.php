<?php

namespace OraMap\Dal;
use OraMap\Models\SqlRecord;

interface IGateway
{
    function find($id);
    function insert(SqlRecord $record);
    function update(SqlRecord $record);
    function delete($id);
    function findByKeyValue($key, $value);
}