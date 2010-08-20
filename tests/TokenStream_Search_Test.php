<?php
    require './test.php';
    require '../src/TokenStream.php';
    
    //                              0      1   2  345678    9   01
    $tokenStream = new TokenStream("<?php\n\n\ndie( ); echo 'hi' ?>");
    $max = count($tokenStream) - 1;
    group('find', function() use($tokenStream, $max) {
        test($tokenStream->find(0, T_EXIT), 2, 'find token');
        test($tokenStream->find(0, array(T_CLOSE_ROUND, T_OPEN_ROUND)), 3, 'find tokens');
        test($tokenStream->find($max, T_CLOSE_TAG), false, 'find from last index');
        test($tokenStream->findEOS(0), 6, 'find eos semicolon');
        test($tokenStream->findEOS(6), 12, 'find eos tag');
        
        test($tokenStream->find(0, T_OPEN_TAG), false, 'find from already matchind token');
        test($tokenStream->find($max, T_EXIT, true), 2, 'find token backwards');
        test($tokenStream->find(0, T_OPEN_TAG, true), false, 'find from first index backwards');
        test($tokenStream->findEOS($max, true), 6, 'find eos semicolon');
    });
    
    group('skip', function() use($tokenStream, $max) {
        test($tokenStream->skip(0, T_WHITESPACE), 2, 'skip token');
        test($tokenStream->skip(0, array(T_WHITESPACE, T_EXIT)), 3, 'skip tokens');
        
        test($tokenStream->skip($max, array(T_WHITESPACE, T_CONSTANT_ENCAPSED_STRING), true), 8, 'skip tokens backwards');
        
        test($tokenStream->skipWhitespace(0), 2, 'skip whitespace');
    });
    
    //                              0     12 34 5678901 234 56 789
    $tokenStream = new TokenStream('<?php (hi,hi,(),((hi),hi)) (()');
    group('complementaryBracket', function() use($tokenStream) {
        test($tokenStream->complementaryBracket(1), 16, 'find forward');
        test($tokenStream->complementaryBracket(15), 9, 'find backwards');
        testException(function() use($tokenStream) { $tokenStream->complementaryBracket(17); }, 'TokenException', 'brackets not matching');
        testException(function() use($tokenStream) { $tokenStream->complementaryBracket(2); }, 'TokenException', 'not a bracket');
    });