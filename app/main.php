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

    $parts = explode(" ", $input);

    ($parts[0] === 'exit') && exit(0);

    printf("%s: command not found\n", $input);
}

