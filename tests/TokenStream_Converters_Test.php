<?php
    require './test.php';
    require '../src/TokenStream.php';
    
    $tokenStream = new TokenStream("<?php\ndie();");
    
    group('__toString', function() use($tokenStream) {
        test((string) $tokenStream, "<?php\ndie();", '(string) $tokenStream');
    });
    
    echo '<p>The debugDump method is hard to test. But try this:
If you like the following output, everything\'s okay. If not, test failed ;)</p>';
    $tokenStream = new TokenStream(file_get_contents(__FILE__));
    $tokenStream->debugDump(true, true, true);