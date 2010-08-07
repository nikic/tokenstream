<?php
    require './test.php';
    require '../src/TokenStream.php';
    
    group('empty stream', function() {
        $tokenStream = new TokenStream();
        test(count($tokenStream), 0, 'argumentless');
        $tokenStream = new TokenStream('');
        test(count($tokenStream), 0, 'empty string');
    });
    
    group('"normal" source', function() {
        test(streamEqual(new TokenStream("<?php\ndie();"), array(
            new Token(T_OPEN_TAG,    "<?php\n"),
            new Token(T_EXIT,        'die'),
            new Token(T_OPEN_ROUND,  '('),
            new Token(T_CLOSE_ROUND, ')'),
            new Token(T_SEMICOLON,   ';'),
        )), true, '"<?php\ndie();"');
    });