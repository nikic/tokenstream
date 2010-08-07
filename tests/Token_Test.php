<?php
    require './test.php';
    require '../src/Token.php';
    
    $token = new Token(T_OPEN_ROUND, '(');
    
    group('magic getters', function() use ($token) {
        testException(function() use ($token) { $token->undefined; }, 'InvalidArgumentException', 'getting undefined property');
        test($token->type,    T_OPEN_ROUND, 'getting property: type');
        test($token->content, '(',          'getting property: content');
        test($token->line,    0,            'getting property: line');
        test($token->id,      1,            'getting property: id');
        test((string) $token, '(',          '(string) $token');
    });
    
    group('Token->is()', function() use ($token) {
        test($token->is(T_OPEN_ROUND),                       true, 'is(token)');
        test($token->is(array(T_CLOSE_ROUND, T_OPEN_ROUND)), true, 'is(array(token, token))');
        test($token->is(T_CLOSE_ROUND, T_OPEN_ROUND),        true, 'is(token, token)');
    });
    
    $clone = clone $token;
    group('clone $token', function() use ($token, $clone) {
        test($clone->id, 2, 'id increment on clone');
        test($clone != $token, true, 'token != clone');
    });