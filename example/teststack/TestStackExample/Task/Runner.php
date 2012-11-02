<?php

namespace TestStackExample\Task;

class Runner
{
    /**
     * @var Runner
     */
    private static $_instance;

    /**
     * @var int|null
     */
    protected $_pid = null;

    /**
     * @return Runner
     */
    public static function getInstance()
    {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }


    /**
     * Runs a task specified on the commandline (CLI)
     *
     *
     * @return void
     */
    public function run()
    {
        echo PHP_EOL . ' =========== ' . __METHOD__ . ' BEGIN =========== ';

        $this->_pid = getmypid();
        $command    = null;

        $args    = $_SERVER['argv'];
        $command = array_shift($args);
        if (!is_array($args)) {
            $args = array();
        }

        $taskName = null;
        if (isset($args[0])) {
            $taskName = (string)$args[0];
        }

        if (empty($taskName)) {
            throw new \Exception(
                'TaskName expected at command line argument 0! ' . __METHOD__
            );
        }


        $reflectionClass = new \ReflectionClass($this);

        $taskClassName   = $reflectionClass->getNamespaceName() . '\\'
            . str_replace(array('.'), array('\\'), $taskName);
        $taskClassExists = false;
        try {
            $taskClassExists = class_exists($taskClassName);
        } catch (\Exception $exception) {
        }

        if (!$taskClassExists) {
            throw new \Exception(
                'TaskClass not found! ' . __METHOD__
                    . '   ' . json_encode(
                    array(
                        'taskName'      => $taskName,
                        'taskClassName' => $taskClassName,
                    )
                )
            );
        }
        $taskInstance = new $taskClassName();
        if (!
        ($taskInstance
            instanceof AbstractTask)
        ) {
            throw new \Exception(
                'Invalid TaskClass!'
                    . ' TaskClass must be instance of AbstractTask! '
                    . __METHOD__
                    . '   ' . json_encode(
                    array(
                        'taskName'      => $taskName,
                        'taskClassName' => $taskClassName,
                        'taskInstance'  => get_class($taskInstance),
                    )
                )
            );

        }

        echo PHP_EOL . ' ' . __METHOD__
            . ' => TASK CLASS: '
            . get_class($taskInstance)
            . PHP_EOL . ' => run() ' . PHP_EOL;

        /** @var $taskInstance AbstractTask */
        $taskStartTS = microtime(true);
        $taskInstance->run();
        $taskStopTS   = microtime(true);
        $taskDuration = $taskStopTS - $taskStartTS;
        echo PHP_EOL . ' =========== ' . __METHOD__ . ' END =========== ';

        var_dump(
            array(
                'duration' => $taskDuration,
            )
        );

        exit(0);
    }
}
