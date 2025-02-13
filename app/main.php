<?php

error_reporting(E_ALL);

// Shell prompt
const PROMPT_SYMBOL = '$ ';

// Command status messages
const COMMAND_NOT_FOUND = '%s: not found';
const SHELL_BUILTIN = '%s is a shell builtin';
const COMMAND_LOCATION = '%s is %s';
const EXIT_SUCCESS = 0;

function UserInputInBash(): string
{
    fwrite(STDOUT, PROMPT_SYMBOL);
    return trim(fgets(STDIN));
}

function executeCommand(string $command, array $args = []): void
{
    switch ($command) {
        case 'echo':
            echo implode(' ', $args) . PHP_EOL;
            break;

        case 'exit':
            exit(EXIT_SUCCESS);

        case 'pwd':
            echo getcwd() . PHP_EOL;
            break;

        case 'type':
            if (empty($args)) {
                printf(COMMAND_NOT_FOUND . PHP_EOL, 'type');
                break;
            }

            $targetBashCommand = $args[0];
            if (isBuiltinCommand($targetBashCommand)) {
                printf(SHELL_BUILTIN . PHP_EOL, $targetBashCommand);
                break;
            }

            $executablePath = findExecutablePath($targetBashCommand);
            if ($executablePath !== false) {
                printf(COMMAND_LOCATION . PHP_EOL, $targetBashCommand, $executablePath);
            } else {
                printf(COMMAND_NOT_FOUND . PHP_EOL, $targetBashCommand);
            }
            break;

        default:
            tryExecuteSystemCommand($command, $args);
            break;
    }
}

function isBuiltinCommand(string $command): bool
{
    return in_array($command, ['echo', 'exit', 'pwd', 'type']);
}

function tryExecuteSystemCommand(string $command, array $args = []): void
{
    $fullCommand = $command . ' ' . implode(' ', $args);
    $paths = getPath();

    foreach ($paths as $path) {
        $fullPath = $path . DIRECTORY_SEPARATOR . $command;
        if (is_executable($fullPath)) {
            $output = [];
            exec($fullCommand, $output);
            echo implode(PHP_EOL, $output) . PHP_EOL;
            return;
        }
    }
    printf(COMMAND_NOT_FOUND . PHP_EOL, $command);
}
function  getPath(): array
{
    return explode(PATH_SEPARATOR, getenv('PATH'));
}
function findExecutablePath(string $command): string|false
{
    if (file_exists($command) && is_executable($command)) {
        return $command;
    }
    $path = getPath();
    foreach ($path as $dir) {
        $fullPath = $dir . DIRECTORY_SEPARATOR . $command;
        if (is_executable($fullPath)) {
            return $fullPath;
        }
    }
    return false;
}

while (true) {
    $input = UserInputInBash();
    if ($input === '') {
        continue;
    }

    $parts = explode(' ', $input);
    $command = $parts[0];
    $arguments = array_slice($parts, 1);

    executeCommand($command, $arguments);
}


