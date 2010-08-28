<?php
    require './test.php';
    require '../src/Token.php';
    
    $token = new Token(T_OPEN_ROUND, '(');
    $funcToken = new Token(T_FUNCTION, 'function', 1);
    
    $clone = clone $token;
    group('clone $token', function() use($token, $clone) {
        test($clone->id, 3, 'id increment on clone');
        test($clone != $token, true, 'token != clone');
    });
    
    group('magic getters', function() use($token, $funcToken) {
        testException(function() use($token) { $token->undefined; }, 'InvalidArgumentException', 'getting undefined property');
        
        test($token->type,     T_OPEN_ROUND, 'getting property: type');
        test($token->content,  '(',          'getting property: content');
        test($token->line,     0,            'getting property: line');
        test($token->id,       1,            'getting property: id');
        test($token->name,     "'('",        'getting property: name (char token)');
        test($funcToken->name, 'T_FUNCTION', 'getting property: name ("real" token)');
        test((string) $token,  '(',          '(string) $token');
    });
    
    group('magic setters', function() use($token, $funcToken) {
        testException(function() use($token) { $token->undefined = 'a'; }, 'InvalidArgumentException', 'setting undefined property');
        testException(function() use($token) { $token->id = 1; }, 'InvalidArgumentException', 'setting id not allowed');
        testException(function() use($token) { $token->name = 1; }, 'InvalidArgumentException', 'setting name not allowed');
        $token = clone $token;
        $token->type    = T_FUNCTION;
        $token->content = 'function';
        $token->line    = 1;
        test(tokenEqual($token, $funcToken), true, 'setting properties');
    });
    
    group('Token->is()', function() use($token) {
        test($token->is(T_OPEN_ROUND),                       true, 'is(token)');
        test($token->is(array(T_CLOSE_ROUND, T_OPEN_ROUND)), true, 'is(array(token, token))');
        test($token->is(T_CLOSE_ROUND, T_OPEN_ROUND),        true, 'is(token, token)');
    });