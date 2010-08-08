<?php
    /*
        This script will remove all whitespace and comments from itself
    */
    
    error_reporting(E_ALL | E_STRICT);
    
    /*
        TokenStream implementation
    */
    require '../src/TokenStream.php';
    
    $tokens = new TokenStream(file_get_contents(__FILE__));
    
    $i = 0;
    while ($i = $tokens->find($i, array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT))) {
        // if the whitespace separates two variables/labels, e.g. new TokenStream or $tokens as $token
        // only convert token to a space character
        if (isset($tokens[$i - 1], $tokens[$i + 1])
            && ($tokens[$i - 1]->is(T_STRING, T_VARIABLE) || ctype_alpha($tokens[$i - 1]->content))
            && ($tokens[$i + 1]->is(T_STRING)             || ctype_alpha($tokens[$i + 1]->content))
        ) {
            $tokens[$i] = new Token(T_WHITESPACE, ' ');
        }
        else {
            unset($tokens[$i--]); // $i-- because this token ought to be rechecked
        }
    }
    
    echo '<pre>', htmlspecialchars($tokens), '</pre>';
    
    /*
        "native" implementation
    */
    $tokens = token_get_all(file_get_contents(__FILE__));
    for ($i = 0; $i < count($tokens); ++$i) {
        if (is_array($tokens[$i]) && in_array($tokens[$i][0], array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT))) {
            // if the whitespace separates two variables/labels, e.g. new TokenStream or $tokens as $token
            // only convert token to a space character
            if (isset($tokens[$i - 1], $tokens[$i + 1])
                && is_array($tokens[$i - 1]) && is_array($tokens[$i + 1])
                && ($tokens[$i - 1][0] == T_STRING || $tokens[$i - 1][0] == T_VARIABLE || ctype_alpha($tokens[$i - 1][1]))
                && ($tokens[$i + 1][0] == T_STRING                                     || ctype_alpha($tokens[$i + 1][1]))
            ) {
                $tokens[$i] = array(T_WHITESPACE, ' ');
            }
            else {
                array_splice($tokens, $i--, 1); // $i-- because this token ought to be rechecked
            }
        }
    }
    
    echo '<pre>';
    foreach ($tokens as $token) {
        if (is_array($token)) {
            echo htmlspecialchars($token[1]);
        }
        else {
            echo htmlspecialchars($token);
        }
    }
    echo '</pre>';