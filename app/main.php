<?php
error_reporting(E_ALL);

// Uncomment this block to pass the first stage
while (true) {
    fwrite(STDOUT, "$ ");
// Wait for user input
    $input = trim(fgets(STDIN));

    if ($input === "") {
        continue;
    }
    printf("%s: command not found\n", $input);
}

