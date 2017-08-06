<?php

/*
Note: This PHP file is irrelevant to the UserUploader repository and is placed here temporarily for convenience.

Output of this file when executed from the command line:
- Output the numbers from 1 to 100
- Where the number is divisible by three (3) output the word “foo”
- Where the number is divisible by five (5) output the word “bar”
- Where the number is divisible by three (3) and (5) output the word “foobar”

Author: Jing C. 
*/

function outputNumbers() {

    $result = "";

    for ($i = 1; $i <= 100; $i++) {

        if ($i % 3 == 0) {
            if ($i % 5 == 0) {
                $result .= "foobar ";
            }
            else $result .= "foo ";
        }

        else if ($i % 5 == 0) {
            $result .= "bar ";
        }

        else $result .= $i . " ";
    }

    echo $result;
}

outputNumbers();

?>