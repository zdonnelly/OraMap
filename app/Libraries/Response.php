<?php

namespace OraMap\Libraries;

class Response
{
    private static $output;
    
    private static $statusCodes = array(
        '200' => ['message' => 'OK'],
        '400' => ['message' => 'Bad Request'],
        '404' => ['message' => 'Not Found'],
        '409' => ['message' => 'Conflict']
    );
    
    public function setOutput($statusCode, $responseMessage) {
        self::$output = array(
            'status' => self::$statusCodes[$statusCode]
        );
    }
    
    public function get() {
        return json_encode(self::$output);
    }
}