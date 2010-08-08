<?php
    require_once 'TokenException.php';
    require_once 'Token.php';
    
    class TokenStream implements Countable, ArrayAccess, IteratorAggregate
    {
        protected static $customTokens = array(
            '(' => T_OPEN_ROUND,
            ')' => T_CLOSE_ROUND,
            '[' => T_OPEN_SQUARE,
            ']' => T_CLOSE_SQUARE,
            '{' => T_OPEN_CURLY,
            '}' => T_CLOSE_CURLY,
            ';' => T_SEMICOLON,
            '.' => T_DOT,
            ',' => T_COMMA,
            '=' => T_EQUAL,
            '<' => T_LT,
            '>' => T_GT,
            '+' => T_PLUS,
            '-' => T_MINUS,
            '*' => T_STAR,
            '/' => T_SLASH,
            '?' => T_QUESTION,
            '!' => T_EXCLAMATION,
            ':' => T_COLON,
            '"' => T_DOUBLE_QUOTES,
            '@' => T_AT,
            '&' => T_AMP,
            '%' => T_PERCENT,
            '|' => T_PIPE,
            '$' => T_DOLLAR,
            '^' => T_CARET,
            '~' => T_TILDE,
            '`' => T_BACKTICK,
            '\\' => T_NS_SEPARATOR,
        );
        
        protected $tokens = array();
        
        /**
        * create TokenStream from source
        * @param string $source code (including <?php)
        */
        public function __construct($source = '') {
            // fast abort on empty source
            if ($source == '') {
                return;
            }
            
            // capture errors
            ob_start();
            
            $tokens = token_get_all($source);
            
            $line = 1;
            foreach ($tokens as $token) {
                if (is_string($token)) {
                    $this->tokens[] = new Token(
                        self::$customTokens[$token],
                        $token,
                        $line
                    );
                }
                else {
                    $this->tokens[] = new Token(
                        $token[0],
                        $token[1],
                        $line
                    );
                    
                    $line += substr_count($token[1], "\n");
                }
            }
            
            // If there are errors, e.g.
            // <b>Warning</b>:  Unexpected character in input:  '\' (ASCII=92) state=1 in [...]
            // iterate through all tokens and compare to source
            if (ob_get_clean() != '') {
                $i = 0; // string offset in source
                $count = count($this->tokens);
                for ($n = 0; $n < $count; ++$n) {
                    $length = strlen($this->tokens[$n]->content);
                    if (substr($source, $i, $length) == $this->tokens[$n]->content) {
                        $i += $length;
                    } else { // token was missing
                        $this->insertToken($n, $source[$i]);
                        ++$i;
                        ++$count;
                    }
                }
            }
        }
        
        /*
            Search methods
        */
        
        /**
        * finds next token of given type
        * @param int $i
        * @param int|array $tokens token or array of tokens to search for
        * @param bool $reverse if true finds previous instead of next token
        * @return int|false returns false if no token found
        */
        public function find($i, $tokens, $reverse = false) {
            if ($reverse) { // find previous
                while ($i--) {
                    if ($this->tokens[$i]->is($tokens)) {
                        return $i;
                    }
                }
            } else { // find next
                $numof = $this->count();
                while (++$i < $numof) {
                    if ($this->tokens[$i]->is($tokens)) {
                        return $i;
                    }
                }
            }
            
            return false;
        }
        
        /**
        * finds next token which is not of given type
        * @param int $i
        * @param int|array $tokens token or array of tokens to skip
        * @param bool $reverse if true skips backwards
        * @return int|false returns false if no token found
        */
        public function skip($i, $tokens, $reverse = false) {
            if ($reverse) { // find previous
                while ($i--) {
                    if (!$this->tokens[$i]->is($tokens)) {
                        return $i;
                    }
                }
            } else { // find next
                $numof = $this->count();
                while (++$i < $numof) {
                    if (!$this->tokens[$i]->is($tokens)) {
                        return $i;
                    }
                }
            }
            
            return false;
        }
        
        // do we need this? Compare:
        // skipWhitespace($i);
        // skip($i, T_WHITESPACE);
        // + 4 characters
        /**
        * skips whitespace (shortcut for skip(, T_WHITESPACE)
        * @param int $i
        * @param bool $reverse if true skips backwards
        * @return int|false returns false if no token found
        */
        public function skipWhitespace($i, $reverse = false) {
            return $this->skip($i, T_WHITESPACE, $reverse);
        }
        
        /**
        * finds next end of statement (that is, a position after which new code may be inserted)
        * @param int $i
        * @param bool $reverse if true finds backwords
        * @return int|false returns false if no token found
        */
        public function findEOS($i, $reverse = false) {
            if ($reverse) { // find previous
                return $this->find(
                    $i,
                    array(
                        T_SEMICOLON,
                        T_CLOSE_CURLY,
                        T_OPEN_CURLY,
                        T_OPEN_TAG,
                    ),
                    true
                );
            } else { // find next
                return $this->find(
                    $i,
                    array(
                        T_SEMICOLON,
                        T_CLOSE_TAG,
                    )
                );
            }
        }
        
        /**
        * finds comlpementary bracket (direction determined using token type)
        * @param int $i
        * @return int
        * @throws TokenException on incorrect nesting
        */
        public function complementaryBracket($i) {
            $complements = array(
                T_OPEN_ROUND   => T_CLOSE_ROUND,
                T_OPEN_SQUARE  => T_CLOSE_SQUARE,
                T_OPEN_CURLY   => T_CLOSE_CURLY,
                T_CLOSE_ROUND  => T_OPEN_ROUND,
                T_CLOSE_SQUARE => T_OPEN_SQUARE,
                T_CLOSE_CURLY  => T_OPEN_CURLY,
            );
            
            if ($this->tokens[$i]->is(T_CLOSE_ROUND, T_CLOSE_SQUARE, T_CLOSE_CURLY)) {
                $reverse = true; // backwards search
            } elseif ($this->tokens[$i]->is(T_OPEN_ROUND, T_OPEN_SQUARE, T_OPEN_CURLY)) {
                $reverse = false; // forwards search
            }
                
            $type = $this->tokens[$i]->type;
            
            $depth = 1;
            while ($depth > 0) {
                if (false === $i = $this->find($i, array($type, $complements[$type]), $reverse)) {
                    throw new TokenException('Opening and closing brackets not matching');
                }
                
                if ($this->tokens[$i]->is($type)) { // opening
                    ++$depth;
                } else { // closing
                    --$depth;
                }
            }

            return $i;
        }
        
        /*
            Stream manipulations
        */
        
        /**
        * append token or stream to stream
        *
        * This function is very flexible about the stream it takes.
        * The stream may consist of sub streams, which are appended recursively.
        * A "stream" here isn't a TokenStream, but any Traversable object or array.
        * Furthermore the stream may be a single element (it is than converted into an array).
        * The elements of the stream may be either Tokens or one-char token strings.
        * Any other elements will simply be dropped, *without* error message.
        *
        * @param mixed $tokenStream
        */
        public function append($tokenStream) {			
            if (!is_array($tokenStream)) {
                $tokenStream = array($tokenStream);
            }
            foreach ($tokenStream as $token) {
                // instanceof Token: append
                if ($token instanceof Token) {
                    $this->tokens[] = $token;
                }
                // one char token: append Token resulting from it
                elseif (is_string($token)) {
                    $this->tokens[] = new Token(
                        self::$customTokens[$token],
                        $token
                    );
                }
                // TokenStream or token array: recursively call appendStream
                elseif ($token instanceof TokenStream || is_array($token)) {
                    $this->append($token);
                }
                // drop anything else (NO! error message)
            }
        }
        
        /**
        * inserts a stream at $i
        *
        * This function is implemented on top of appendStream, therefore the notes
        * there apply to the tokenStream being inserted, too.
        *
        * @param int $i offset in token array
        * @param mixed $tokenStream
        */
        public function insert($i, $tokenStream) {
            if ($i == $this->count() - 1) { // end => append
                $this->append($tokenStream);
                return;
            }
            
            // remove following stream to append later
            $after = array_splice($this->tokens, $i);
            
            // "magic" append
            $this->append($tokenStream);
            
            // append $after
            $this->tokens = array_merge($this->tokens, $after);
        }
        
        /**
        * get and remove substream or token
        * @param int $i
        * @param int $to
        */
        public function extract($i, $to = null) {
            if ($to === null) {
                $tokens = array_splice($this->tokens, $i, 1, array());
                return $tokens[0];
            } else {
                $tokenStream = new TokenStream;
                $tokenStream->append(
                    array_splice($this->tokens, $i, $to - $i + 1, array())
                );
                return $tokenStream;
            }
        }
        
        /**
        * get substream
        * @param int $from
        * @param int $to
        */
        public function get($from, $to) {
            $tokenStream = new TokenStream;
            $tokenStream->append(
                array_slice($this->tokens, $from, $to - $from + 1)
            );
            
            return $tokenStream;
        }
        
        /*
            Converters
        */
        
        /**
        * convert token stream to source code
        * @return string
        */
        public function __toString() {
            $string = '';
            foreach ($this->tokens as $token) {
                $string .= $token;
            }
            return $string;
        }
        
        /**
        * dumps a formatted version of the token stream
        * @param bool $indentBrackets whether to indent on brackets
        * @param bool $convertWhitespace whether to convert whitespace characters to
        *                                \r, \n and \t string literals and display grey
        * @param bool $hideWhitespaceTokens whether to hide all T_WHITESPACE tokens
        */
        public function debugDump($indentBrackets = false, $convertWhitespace = false, $hideWhitespaceTokens = false) {
            $indent = 0;
            echo '<pre style="color:grey">';
            foreach ($this->tokens as $token) {
                if ($hideWhitespaceTokens && $token->is(T_WHITESPACE)) {
                    continue;
                }
                
                if ($token->is(T_CLOSE_ROUND, T_CLOSE_SQUARE, T_CLOSE_CURLY)) {
                    --$indent;
                }
                if ($indentBrackets) {
                    echo str_pad('', $indent, "\t");
                }
                if ($token->is(T_OPEN_ROUND, T_OPEN_SQUARE, T_OPEN_CURLY)) {
                    ++$indent;
                }
                
                echo '"<span style="color:black">';
                if (!$convertWhitespace) {
                    echo htmlspecialchars($token->content);
                } else {
                    foreach (str_split($token->content) as $char) {
                        if ($char == "\n") {
                            echo '<span style="color:grey">\n</span>';
                        } elseif ($char == "\r") {
                            echo '<span style="color:grey">\r</span>';
                        } elseif ($char == "\t") {
                            echo '<span style="color:grey">\t</span>';
                        } elseif ($char == '<') {
                            echo '&lt;';
                        } else {
                            echo $char;
                        }
                    }
                }
                echo '</span>"';
                if (!in_array($token->type, self::$customTokens)) {
                    echo ' ', token_name($token->type);
                }
                if ($token->line != 0) {
                    echo ' line: ', $token->line;
                }
                echo PHP_EOL;
            }
            echo '</pre>';
        }
        
        /*
            Interfaces
        */
        
        /**
        * counts number of tokens (interface: Countable)
        * @return int
        */
        public function count() {
            return count($this->tokens);
        }
        
        /**
        * returns iterator (interface: IteratorAggregate)
        * @return ArrayIterator
        */
        public function getIterator() {
            return new ArrayIterator($this->tokens);
        }
        
        /**
        * checks if offset exists in token array (interface: ArrayAccess)
        * @param int $offset
        * @return bool
        */
        public function offsetExists($offset)
        {
            return isset($this->tokens[$offset]);
        }
        
        /**
        * get offset from token array (interface: ArrayAccess)
        * @param int $offset
        * @return Token
        * @throws OutOfBoundException if offset doesn't exist
        */
        public function offsetGet($offset)
        {
            if (!isset($this->tokens[$offset])) {
                throw new OutOfBoundsException('offset does not exist');
            }
            
            return $this->tokens[$offset];
        }
        
        /**
        * set offset in token array (interface: ArrayAccess)
        * @param int $offset
        * @param Token $value
        */
        public function offsetSet($offset, $value)
        {
            if (!$value instanceof Token) {
                throw new InvalidArgumentException('Cannot set offset '.$offset.': Expecting Token');
            }
            
            if ($offset === null) {
                $this->tokens[] = $value;
            }
            else {
                $this->tokens[$offset] = $value;
            }
        }
        
        /**
        * unset offset in token array
        * @param int $offset
        */
        public function offsetUnset($offset)
        {
            if (!isset($this->tokens[$offset])) {
                throw new OutOfBoundsException('offset does not exist');
            }
            
            // need splice here to move other tokens down
            array_splice($this->tokens, $offset, 1);
        }
    }