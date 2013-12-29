Feature: Extract Method Object
    In order to extract a list of statements into its own object
    As a developer
    I need an extract method object refactoring

    Scenario: "Extract side effect free line into method object"
        Given a PHP File named "src/Foo.php" with:
            """
            <?php
            class Foo
            {
                public function operation()
                {
                    echo "Hello World";
                }
            }
            """
        When I use refactoring "extract-method-object" with:
            | arg       | value       |
            | file      | src/Foo.php |
            | range     | 6-6         |
            | newfile   | src/Bar.php |
            | newclass  | Bar         |
        Then the PHP File "src/Foo.php" should be refactored:
            """
            --- a/vfs://project/src/Foo.php
            +++ b/vfs://project/src/Foo.php
            @@ -4,5 +4,10 @@
                 public function operation()
                 {
            -        echo "Hello World";
            +        $object = new Bar();
            +        $object->invoke();
                 }
             }
            --- a/vfs://project/src/Bar.php
            +++ b/vfs://project/src/Bar.php
            @@ 0,0 0,10 @@
            +<?php
            +
            +class Bar
            +{
            +    public function invoke()
            +    {
            +        echo "Hello World";
            +    }
            +}
            """
