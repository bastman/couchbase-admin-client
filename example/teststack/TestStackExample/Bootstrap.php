<?php
/**
 * Created by JetBrains PhpStorm.
 * User: thelittlenerd87
 * Date: 17.08.12
 * Time: 10:14
 * To change this template use File | Settings | File Templates.
 */
namespace TestStackExample;

class Bootstrap
{
    /**
     * @var bool
     */
    private $_isInitialized;

    /**
     * @var Bootstrap
     */
    private static $_instance;

    /**
     * @return Bootstrap
     */
    public static function getInstance()
    {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * @return Bootstrap
     */
    public function init()
    {
        if (!$this->_isInitialized) {
            $this->_init();
            $this->_isInitialized = true;
        }

        return $this;
    }

    /**
     * @param  string $className
     * @return bool
     */
    /*
    public function phpAutoLoader($className)
    {
        // quick-n-dirty ....

        $exampleProjectPath = dirname(__FILE__);
        $srcPath = $exampleProjectPath . '/../../src';

        $classPathList = array(
           $srcPath,
           $exampleProjectPath,
        );

        $filename = ltrim((string)$className, '\\') . '.php';

        foreach($classPathList as $classPath) {
            $classPath = realpath($classPath);
            if(!$classPath) {

                continue;
            }
            $location = $classPath . '/' . $filename;
            $location = str_replace(
                array('\\', '_' ),
                array('/','/'),
                $location
            );

            $location = realpath($location);
            if(!$location) {

                continue;
            }
            require_once($location);

            return (class_exists($className, false)===true);
        }

        return false;
    }
    */


    /**
     * @return Hardcoded shutdown handler to detect critical PHP errors.
     */
    public function phpShutdownHandler()
    {

        $error = error_get_last();

        if ($error === null) {
            // no error, we have a "normal" shut down (script is finished).
            return;
        }


        $responseData = array(
            "error"  => array(
                "class"   => str_replace("_", ".", get_class($this)),
                "message" => "shutdown error"
            ),
            "result" => null,
        );
        echo json_encode($responseData);

    }


    // ++++++++++++++++++++++++++++++++++++++++++++++++++++++

    /**
     *
     * @return void
     */
    protected function _init()
    {
        /*
            RECOMMENDED SETUP
            =================
        */
        ini_set("display_errors", true);
        error_reporting(E_ALL | E_STRICT & ~E_NOTICE);

        // turn on error exceptions
        set_error_handler(
            function ($errno, $errstr, $errfile, $errline) {
                throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
            }
        );

        // try catch fatal errors
        register_shutdown_function(array($this, 'phpShutdownHandler'));

        // spl_autoload_register(array($this, 'phpAutoLoader'));

        ini_set("display_errors", false);
    }


}