<?php
namespace Libraries;

class Logger
{
    private $logsDirName = 'Logs';

    private static $instance;

    public static function getInstance()
    {
        if(null === static::$instance)
        {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Protected constructor to prevent creating a new instance of the
     * *Singleton* via the `new` operator from outside of this class.
     */
    protected function __construct(){}


    /**
     * Private clone method to prevent cloning of the instance of the
     * *Singleton* instance.
     *
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * Private unserialize method to prevent unserializing of the *Singleton*
     * instance.
     *
     * @return void
     */
    private function __wakeup()
    {
    }

    public function log($level = "INFO", $message)
    {
        $logsRelativePath = dirname(__DIR__) . '\\' . $this->logsDirName;
        $date = new \DateTime();
        $logString = $date->format('Y-m-d H:i:s') . "\t" . $level . "\t" . trim(preg_replace('/\s\s+/', ' ', $message)) . PHP_EOL;

        $fileName = $date->format('Y-m-d') . '.LOG';
        if(is_dir($logsRelativePath))
        {
            if (file_exists($logsRelativePath . '\\' . $fileName))
            {
                file_put_contents($logsRelativePath . '\\' . $fileName, $logString, FILE_APPEND);
            }
            else
            {
                file_put_contents($logsRelativePath . '\\' . $fileName, $logString);
            }
        }
    }
}