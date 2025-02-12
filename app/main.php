<?php

error_reporting(E_ALL);

const PROMPT_SYMBOL = '$ ';
const COMMAND_NOT_FOUND = '%s: command not found';
const INVALID_COMMAND = 'invalid_command: not found';
const SHELL_BUILTIN = '%s is a shell builtin';
const COMMAND_LOCATION = '%s is %s';
const EXIT_SUCCESS = 0;

function promptUserInput()
{
    fwrite(STDOUT, PROMPT_SYMBOL);
    return trim(fgets(STDIN));
}

function processCommand($input, $commandsList)
{
    if ($input === '') {
        return;
    }

    $parts = explode(' ', $input);
    $command = $parts[0];
    $arguments = array_slice($parts, 1);

    if (isset($commandsList[$command])) {
        $commandsList[$command]($arguments, $commandsList);
    } else {
        printf(COMMAND_NOT_FOUND . PHP_EOL, $input);
    }
}

$commandsList = [
    'echo' => function ($arguments) {
        echo implode(' ', $arguments) . PHP_EOL;
    },
    'exit' => function () {
        exit(EXIT_SUCCESS);
    },
    'type' => function ($arguments, $commandsList) {
        if (empty($arguments)) {
            echo INVALID_COMMAND . PHP_EOL;
            return;
        }
        $target_command = $arguments[0];

        if (isset($commandsList[$target_command])) {
            printf(SHELL_BUILTIN . PHP_EOL, $target_command);
            return;
        }
        $executablePath = findExecutablePath($target_command);
        if ($executablePath) {
            printf(COMMAND_LOCATION . PHP_EOL, $target_command, $executablePath);
            return;
        }
        printf(COMMAND_NOT_FOUND . PHP_EOL, $target_command);
    },
];

function getPath()
{
    return explode(PATH_SEPARATOR, getenv('PATH'));
}

function findExecutablePath($command)
{
    if (file_exists($command) && is_executable($command)) {
        return $command;
    }

    $path = getPath();
    foreach ($path as $dir) {
        $fullPath = $dir . DIRECTORY_SEPARATOR . $command;
        if (is_executable($fullPath)) {
            return ($fullPath);
        }
    }
    return false;
}

while (true) {
    $input = promptUserInput();
    processCommand($input, $commandsList);
}
