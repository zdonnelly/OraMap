<?php

namespace OraMap\Models;

interface IRecord
{
    function addField($key, $value);
    function getField($key);
    function getRecord();
    function setField($key, $value);

}