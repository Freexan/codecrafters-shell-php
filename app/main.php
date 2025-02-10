<?php

error_reporting(E_ALL);

while (true) {
    fwrite(STDOUT, "$ ");
    // Wait for user input
    $input = trim(fgets(STDIN));

    if ($input === '') {
        continue;
    }

    $parts = explode(' ', $input);
    $command = $parts[0]; // Use variable instead of constant
    $arguments = array_slice($parts, 1);

    if ($command === 'exit') {
        exit(0);
    } elseif ($command === 'echo') {
        echo implode(' ', $arguments) . PHP_EOL;
    } else {
        printf("%s: command not found\n", $input);
    }
}

