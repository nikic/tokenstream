<?php
    require './test.php';
    require '../src/TokenStream.php';
    
    $tokenStream = new TokenStream("<?php\ndie();");
    
    group('interface Countable', function() use($tokenStream) {
        test(count($tokenStream), 5, 'count($tokenStream)');
    });
    
    group('interface Iterator', function() use($tokenStream) {
        $source = '';
        foreach ($tokenStream as $token) {
            $source .= $token;
        }
        test($source, "<?php\ndie();", 'source restauration');
        
        $tokenStream->seek(2);
        test($tokenStream->current()->type, T_OPEN_ROUND, 'seek');
        testException(function() use($tokenStream) { $tokenStream->seek(5); }, 'OutOfBoundsException', 'seek out of bounds');
        
        // test manipulation while iterating
        
        // unset()
        $tokens = clone $tokenStream;
        $tokens->seek(2);
        unset($tokens[1]);
        test($tokens->key(), 1, 'unset before position');
        
        // extract() single
        $tokens = clone $tokenStream;
        $tokens->seek(2);
        $tokens->extract(1);
        test($tokens->key(), 1, 'extract single before position');
        
        // extract() multiple
        $tokens = clone $tokenStream;
        $tokens->seek(2);
        $tokens->extract(0, 1);
        test($tokens->key(), 0, 'extract multiple before position');
        $tokens = clone $tokenStream;
        $tokens->seek(2);
        $tokens->extract(1, 3);
        test($tokens->key(), 1, 'extract multiple over position');
        
        // insert()
        $tokens = clone $tokenStream;
        $tokens->seek(2);
        $tokens->insert(1, array('(', ')'));
        test($tokens->key(), 4, 'insert before position');
        $tokens->insert(1, array('(', ')', array('(', ')')));
        test($tokens->key(), 8, 'insert recursive before position');
    });
    
    group('interface ArrayAccess', function() use($tokenStream) {
        $tokenStream = clone $tokenStream;
        test($tokenStream[0]->content, "<?php\n", 'get offset');
        $tokenStream[0] = new Token(
            T_OPEN_TAG,
            "<?\n",
            1
        );
        test($tokenStream[0]->content, "<?\n", 'set offset');
        $tokenStream[] = new Token(
            T_CLOSE_TAG,
            '?>',
            2
        );
        test($tokenStream[5]->content, '?>', 'append');
        test(isset($tokenStream[6]), false, 'isset');
        unset($tokenStream[0]); // pass if no error
        test(isset($tokenStream[0]), true, 'unset moves tokens down');
        testException(function() use($tokenStream) { $tokenStream[5]; }, 'OutOfBoundsException', 'getting non existing offset');
        testException(function() use($tokenStream) { unset($tokenStream[5]); }, 'OutOfBoundsException', 'unsetting non existing offset');
        testException(function() use($tokenStream) { $tokenStream[] = 0; }, 'InvalidArgumentException', 'setting to non-token');
    });