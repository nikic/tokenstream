<?php
    /* A list of all tokens may be obtained at php.net/tokens.
       Here I define some tokens introduced in PHP 5.3.
       Furthermore for all one-char tokens a name is defined,
       to allow consistent work */
        
    // PHP 5.3 tokens for PHP 5.2
    if (!defined('T_DIR'))
        define('T_DIR',          379);
    if (!defined('T_GOTO'))
        define('T_GOTO',         333);
    if (!defined('T_NAMESPACE'))
        define('T_NAMESPACE',    377);
    if (!defined('T_NS_C'))
        define('T_NS_C',         378);
    if (!defined('T_NS_SEPARATOR'))
        define('T_NS_SEPARATOR', 380);
    if (!defined('T_USE'))
        define('T_USE',          340);
    
    // custom one char tokens
    define('T_OPEN_ROUND',    1001);
    define('T_CLOSE_ROUND',   1002);
    define('T_OPEN_SQUARE',   1003);
    define('T_CLOSE_SQUARE',  1004);
    define('T_OPEN_CURLY',    1005);
    define('T_CLOSE_CURLY',   1006);
    define('T_SEMICOLON',     1007);
    define('T_DOT',           1008);
    define('T_COMMA',         1009);
    define('T_EQUAL',         1010);
    define('T_LT',            1011);
    define('T_GT',            1012);
    define('T_PLUS',          1013);
    define('T_MINUS',         1014);
    define('T_STAR',          1015);
    define('T_SLASH',         1016);
    define('T_QUESTION',      1017);
    define('T_EXCLAMATION',   1018);
    define('T_COLON',         1019);
    define('T_DOUBLE_QUOTES', 1020);
    define('T_AT',            1021);
    define('T_AMP',           1022);
    define('T_PERCENT',       1023);
    define('T_PIPE',          1024);
    define('T_DOLLAR',        1025);
    define('T_CARET',         1026);
    define('T_TILDE',         1027);
    define('T_BACKTICK',      1028);
    
    
    class Token
    {
        // every token has an internal identifier to make it unique
        // even if there were another token there all the other properties were identical.
        protected static $currentId = 0;
        protected $id;
        
        protected $type;
        protected $content;
        protected $line;
        
        /**
        * create new token
        *
        * @param int    $type    type of token, e.g. T_VARIABLE
        * @param string $content content of token, e.g. $foo
        * @param int    $line    line in source
        */
        public function __construct($type, $content, $line = 0) {
            $this->id = ++self::$currentId;
            
            $this->type = $type;
            $this->content = $content;
            $this->line = $line;
        }
        
        /**
        * clone token, incrementing id
        */
        public function __clone() {
            $this->id = ++self::$currentId;
        }
        
        /**
        * get a property
        * @param string $name name of the property (id, type, content, line allowed)
        * @return mixed
        */
        public function __get($name) {
            if (!isset($this->$name)) {
                throw new InvalidArgumentException('Property ' . $name . ' does not exist');
            }
            
            return $this->$name;
        }
        
        /**
        * convert to string
        * @return string
        */
        public function __toString() {
            return $this->content;
        }
        
        /**
        * check whether token is of a certain type
        * @param int|array $type either a token type or an array of token types
        * @param int       $... instead of array as first parameter the token types
        *                       may be passed directly using ->is(type, type, type, ...)
        * @return bool
        */
        public function is($type) {
            return $type === $this->type
                   || (func_num_args() == 1 && is_array($type) && in_array($this->type, $type))
                   || (func_num_args() > 1 && ($args = func_get_args()) && in_array($this->type, $args));
        }
    }