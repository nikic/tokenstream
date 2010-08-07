<?php
    error_reporting(E_ALL | E_STRICT);

    function group($title, $lambda)
    {
        echo '<pre>', htmlspecialchars($title), ':', PHP_EOL;
        $lambda();
        echo '</pre>';
    }
    
    function renderPass($title)
    {
        echo "\t", 'passed ', htmlspecialchars($title), PHP_EOL;
    }
    function renderFail($title, $expected, $got)
    {
        echo "\t", '<span style="color:red">failed (expected "', htmlspecialchars($expected), '", got "', htmlspecialchars($got), '") ', htmlspecialchars($title), '</span>', PHP_EOL;
    }

    function test($value, $expected, $title)
    {
        if ($value === $expected) {
            renderPass($title);
        } else {
            renderFail($title, $expected, $value);
        }
    }
    
    function testException($lambda, $type, $title)
    {
        try {
            $lambda();
            renderFail($title, 'exception', 'no exception');
        }
        catch (Exception $e) {
            if ($e instanceof $type) {
                renderPass($title);
            } else {
                renderFail($title, $type, get_class($e));
            }
        }
    }
    
    
    function tokenEqual(Token $token1, Token $token2)
    {
        return $token1->type === $token2->type
               && $token1->content === $token2->content;
    }
    
    function streamEqual($stream1, $stream2)
    {
        if (count($stream1) != count($stream2)) {
            return false;
        }
        
        foreach ($stream1 as $i => $token) {
            if (!tokenEqual($stream1[$i], $stream2[$i])) {
                return false;
            }
        }
        
        return true;
    }