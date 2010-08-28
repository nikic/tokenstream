<?php
    require './test.php';
    require '../src/TokenStream.php';
    
    group('empty stream', function() {
        $tokenStream = new TokenStream();
        test(count($tokenStream), 0, 'argumentless');
        $tokenStream = new TokenStream('');
        test(count($tokenStream), 0, 'empty string');
    });
    
    group('source', function() {
        test(streamEqual(new TokenStream("<?php\ndie();"), array(
            new Token(T_OPEN_TAG,    "<?php\n"),
            new Token(T_EXIT,        'die'),
            new Token(T_OPEN_ROUND,  '('),
            new Token(T_CLOSE_ROUND, ')'),
            new Token(T_SEMICOLON,   ';'),
        )), true, 'normal source');
        
        test(streamEqual(new TokenStream("<?php namespace\A"), array(
            new Token(T_OPEN_TAG,     "<?php "),
            new Token(T_NAMESPACE,    'namespace'),
            new Token(T_NS_SEPARATOR, '\\'),
            new Token(T_STRING,       'A'),
        )), true, 'source with 5.3 tokens');
    });