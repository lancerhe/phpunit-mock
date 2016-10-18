<?php
/**
 * Mock Class
 */
class PHPUnit_Extensions_MockClass {

    /**
     * @var array
     */
    static public $mock_objects = array();

    /**
     * @var string
     */
    protected $_class_name;

    /**
     * @var string
     */
    protected $_class_alias;

    /**
     * @var array
     */
    protected $_methods;

    /**
     * @var boolean
     */
    protected $_mock = false;

    /**
     * Construct
     *
     * @param string                     $_class_name
     * @param PHPUnit_Framework_TestCase $testcase
     */
    public function __construct($class, $methods, PHPUnit_Framework_TestCase $testcase) {
        if ( ! class_exists($class)) {
            throw new InvalidArgumentException("Invalid class name '$class'");
        }
        if ( array_key_exists($class, self::$mock_objects) ) {
            throw new RuntimeException("Can not create second class mock for '$class'");
        }

        $this->_class_name  = $class;
        $this->_class_alias = uniqid("class_");
        $this->_methods     = is_string($methods) ? [$methods] : $methods;
        self::$mock_objects[$this->_class_name] = $testcase->getMock($this->_class_alias, $this->_methods);
        $this->mock();
    }

    /**
     * Mock
     *
     * @return \MockFunction
     */
    public function mock() {
        if ( $this->_mock ) 
            return $this;

        $this->_mock = true;

        foreach ($this->_methods as $method) {
            if(method_exists($this->_class_name, $method)){
                runkit_method_rename( $this->_class_name, $method, "_origin_" . $method);
            }
            runkit_method_add   ( $this->_class_name, $method, '', $this->getMockCode($method));
        }
        return $this;
    }

    /**
     * Restore
     *
     * @return \MockFunction
     */
    public function restore() {
        if ( ! $this->_mock) 
            return $this;

        $this->_mock = false;
        foreach ($this->_methods as $method) {
            runkit_method_remove( $this->_class_name, $method);
            if(method_exists($this->_class_name, "_origin_" . $method)){
                runkit_method_rename( $this->_class_name, "_origin_" . $method, $method);
            }
        }
        return $this;
    }

    /**
     * Destruct
     */
    public function __destruct() {
        unset(self::$mock_objects[$this->_class_name]);
        $this->restore();
    }

    /**
     * Get mock code
     *
     * @param  string $method
     * @return string
     */
    public function getMockCode($method) {
        $self       = __CLASS__;
        $class_name = var_export($this->_class_name, true);
        $method     = var_export($method, true);

        return "
            \$callback = array($self::getMock($class_name), $method);
            return call_user_func_array(\$callback, func_get_args());
        ";
    }

    /**
     * Registers a new expectation in the mock object and returns the match
     * object which can be infused with further details.
     *
     * @param  PHPUnit_Framework_MockObject_Matcher_Invocation $matcher
     * @return PHPUnit_Framework_MockObject_Builder_InvocationMocker
     */
    public function expects(PHPUnit_Framework_MockObject_Matcher_Invocation $matcher) {
        return self::getMock($this->_class_name)->expects($matcher);
    }

    /**
     * Get mock
     *
     * @param  string $class
     * @return PHPUnit_Framework_MockObject_MockObject
     * @throws \RuntimeException
     */
    public static function getMock($class) {
        if ( ! array_key_exists($class, self::$mock_objects) )
            throw new \RuntimeException("Failed to get mock object for function '$class'");
        return self::$mock_objects[$class];
    }
}
