<?php

error_reporting(E_ALL);

// Shell prompt
const PROMPT_SYMBOL = '$ ';

// Command status messages
const COMMAND_NOT_FOUND = '%s: not found';
const DIRECTORY_OR_FILE_NOT_FOUND = '%s: No such file or directory';
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

        case 'cat':
            // Перевірка існування файлів перед виконанням
            foreach ($args as $file) {
                if (!file_exists($file)) {
                    fprintf(STDOUT, DIRECTORY_OR_FILE_NOT_FOUND . PHP_EOL, $file);
                    return;
                }
            }
            tryExecuteSystemCommand($command, $args);
            break;

        case 'exit':
            exit(EXIT_SUCCESS);

        case 'pwd':
            echo getcwd() . PHP_EOL;
            break;

        case 'cd':
            if ($args[0] === '~') {
                $args[0] = getenv('HOME');
            }
            if (!is_dir($args[0])) {
                fprintf(STDOUT, DIRECTORY_OR_FILE_NOT_FOUND . PHP_EOL, $args[0]);
                break;
            }
            $targetDirectory = $args[0];
            if (!chdir($targetDirectory)) {
                fprintf(STDOUT, DIRECTORY_OR_FILE_NOT_FOUND . PHP_EOL, $targetDirectory);
            } else {
                break;
            }
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
    $fullCommand = $command . ' ' . implode(' ', array_map('escapeshellarg', $args));
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

function getPath(): array
{
    return explode(PATH_SEPARATOR, getenv('PATH'));
}

function findExecutablePath(string $command): string|false
{
    $pathDirs = array_merge(['.'], getPath());

    foreach ($pathDirs as $dir) {
        $fullPath = $dir . DIRECTORY_SEPARATOR . $command;
        if (file_exists($fullPath) && is_executable($fullPath)) {
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

    $parsedInput = parseQuotedArguments($input);
    $command = array_shift($parsedInput);
    $arguments = $parsedInput;

    executeCommand($command, $arguments);
}

function parseQuotedArguments(string $input): array
{
    $result = [];
    $current = '';
    $insideSingleQuote = false;
    $insideDoubleQuote = false;

    for ($i = 0; $i < strlen($input); $i++) {
        $char = $input[$i];

        if ($char === "'" && !$insideDoubleQuote) {
            $insideSingleQuote = !$insideSingleQuote;
        } elseif ($char === '"' && !$insideSingleQuote) {
            $insideDoubleQuote = !$insideDoubleQuote;
        } elseif ($char === ' ' && !$insideSingleQuote && !$insideDoubleQuote) {
            if ($current !== '') {
                $result[] = $current;
                $current = '';
            }
        } else {
            $current .= $char;
        }
    }

    if ($current !== '') {
        $result[] = $current;
    }

    return $result;
}
