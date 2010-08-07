<?php
    require './test.php';
    require '../src/TokenStream.php';
    
    $tokenStream = new TokenStream("<?php\ndie();");
    
    group('get', function() use($tokenStream) {
        test(streamEqual($tokenStream->get(1, 2),
            array(
                new Token(T_EXIT, 'die'),
                new Token(T_OPEN_ROUND, '('),
            )
        ), true, 'get(int, int)');
    });
    
    group('extract', function() use($tokenStream) {
        $tokenStream = clone $tokenStream;
        
        test(streamEqual($tokenStream->extract(1, 2),
            array(
                new Token(T_EXIT, 'die'),
                new Token(T_OPEN_ROUND, '('),
            )
        ), true, 'return value of extract(int, int)');
        test(streamEqual($tokenStream->get(0, count($tokenStream) - 1),
            array(
                new Token(T_OPEN_TAG, "<?php\n"),
                new Token(T_CLOSE_ROUND, ')'),
                new Token(T_SEMICOLON, ';'),
            )
        ), true, 'stream change of extract(int, int)');
        
        test(tokenEqual($tokenStream->extract(2), new Token(T_SEMICOLON, ';')), true, 'return value of extract(int)');
        test(streamEqual($tokenStream->get(0, count($tokenStream) - 1),
            array(
                new Token(T_OPEN_TAG, "<?php\n"),
                new Token(T_CLOSE_ROUND, ')'),
            )
        ), true, 'stream change of extract(int)');
    });
    
    group('append', function() use($tokenStream) {
        $tokenStream = clone $tokenStream;
        
        $tokenStream->append(array(
            new Token(T_WHITESPACE, "\n"),
            '{', '}',
            array(
                ';',
            ),
        ));
        test((string) $tokenStream, "<?php\ndie();\n{};", 'append token array');
        
        $tokenStream->append(';');
        test((string) $tokenStream, "<?php\ndie();\n{};;", 'append single token');
    });
    
    group('insert', function() use($tokenStream) {
        $tokenStream = clone $tokenStream;
        
        $tokenStream->insert(1, array(
            '{', '}',
            array(
                ';',
                new Token(T_WHITESPACE, "\n"),
            ),
        ));
        test((string) $tokenStream, "<?php\n{};\ndie();", 'insert token array');
        
        $tokenStream->insert(4, ';');
        test((string) $tokenStream, "<?php\n{};;\ndie();", 'insert single token');
    });