<?php

namespace OraMap\Models;
use Libraries\Logger;

class SqlRecord implements SqlRecordInterface
{
    private $logger;
    private $dateTimeFormat = 'Y-m-d H:i:s';

    protected $record = array(
        'id' => ''
    );

    public function __construct() {
        $this->logger = Logger::getInstance();
    }

    public function addField($key, $value)
    {
        if(strtolower($key) == 'id')
        {
            $this->record[$key] = strtolower($value);
        }
        else
        {
            $this->record[$key] = $value;
        }
    }

    public function addMultipleFields(array $fields)
    {
        foreach($fields as $key => $value)
        {
            $this->addField($key, $value);
        }
    }

    public function getField($key)
    {
        if($this->keyExists($key))
        {
            return $this->record[$key];
        }

        return null;
    }

    public function getDateTimeFormat()
    {
        return $this->dateTimeFormat;
    }

    public function setMultipleFields(array $fields)
    {
        foreach($fields as $key => $value)
        {
            $this->setField($key, $value);
        }
    }

    public function setField($key, $value)
    {
        if($this->keyExists($key))
        {
            if(strtolower($key) == 'id')
            {
                $this->record[$key] = strtolower($value);
            }
            else
            {
                $this->record[$key] = $value;
            }
            return true;
        }
        return false;
    }

    private function keyExists($key)
    {
        return array_key_exists($key, $this->record);
    }

    public function getInsertString()
    {
        $keyString = "";
        $valString = "";

        foreach($this->record as $key => $value) {
            if(strtolower($key) == 'id')
            {
                $valString .= "seq,";
            }
            else
            {
                if($value instanceof \DateTime)
                {
                    $valString .= "to_date(:".$key."_bv, 'YYYY-MM-DD HH24:MI:SS'),";
                }
                else
                {
                    $valString .=  ":".$key."_bv,";
                }

            }

            $keyString .= $key.",";

        }

        $valString = rtrim($valString, ",");
        $keyString = rtrim($keyString, ",");

        $string = "({$keyString}) values ({$valString})";

        $this->logger->log('DEBUG', $string);

        return $string;
    }

    public function getUpdateString()
    {
        $string = "";

        foreach($this->record as $key => $value) {
            if(strtolower($key) == 'id')
            {
                continue;
            }
            else
            {
                if($value instanceof \DateTime)
                {
                    $string .= "{$key} = to_date(:".$key."_bv, 'YYYY-MM-DD HH24:MI:SS'),";
                }
                else
                {
                    $string .= "{$key} = '{$value}',";
                }

            }
        }
        $string = rtrim($string, ',');

        $this->logger->log('DEBUG', $string);

        return $string;
    }

    public function getRecord()
    {
        return $this->record;
    }
}