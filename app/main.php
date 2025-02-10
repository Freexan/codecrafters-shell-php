<?php

error_reporting(E_ALL);

function promptUserInput()
{
    fwrite(STDOUT, "$ ");
    return trim(fgets(STDIN));
}

function processCommand($input)
{
    if ($input === '') {
        return;
    }

    $parts = explode(' ', $input);
    $command = $parts[0];
    $arguments = array_slice($parts, 1);

    executeCommand($command, $arguments, $input);
}

function executeCommand($command, $arguments, $input)
{
    if ($command === 'exit') {
        exit(0);
    } elseif ($command === 'echo') {
        echo implode(' ', $arguments) . PHP_EOL;
    } elseif ($command === 'type') {
        if ($arguments[0] === 'echo') {
            echo "echo is a shell builtin" . PHP_EOL;
        } elseif ($arguments[0] === 'exit') {
            echo "exit is a shell builtin" . PHP_EOL;
        } elseif ($arguments[0] === 'type') {
            echo "type is a shell builtin" . PHP_EOL;
        } else {
            printf("%s: not found\n", $arguments[0]);
        }
    } else {
        printf("%s: command not found\n", $input);
    }
}

while (true) {
    $input = promptUserInput();
    processCommand($input);
}
