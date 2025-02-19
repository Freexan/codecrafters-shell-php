<?php

error_reporting(E_ALL);

$generalConfig = require 'config/general.php';
$systemConfig = require 'config/system.php';
$commandsConfig = require 'config/commands.php';

$config = array_merge($generalConfig, $systemConfig, $commandsConfig);


function UserInputInBash(string $promptSymbol): string
{
    fwrite(STDOUT, $promptSymbol);
    return trim(fgets(STDIN));
}

function executeCommand(string $command, array $args, array $config): void
{
    switch ($command) {
        case $config['lookup']['echo']:
            echo implode(' ', $args) . PHP_EOL;
            break;

        case $config['lookup']['cat']:
            foreach ($args as $file) {
                if (!file_exists($file)) {
                    fprintf(STDOUT, $config['directoryOrFileNotFound'] . PHP_EOL, $file);
                    return;
                }
            }
            tryExecuteSystemCommand($command, $args, $config);
            break;

        case $config['lookup']['exit']:
            exit($config['exitSuccess']);

        case $config['lookup']['pwd']:
            echo getcwd() . PHP_EOL;
            break;

        case $config['lookup']['cd']:
            if (empty($args)) {
                fprintf(STDOUT, $config['directoryOrFileNotFound'] . PHP_EOL, '~');
                return;
            }
            if ($args[0] === '~') {
                $args[0] = $config['home'];
            }
            if (!is_dir($args[0])) {
                fprintf(STDOUT, $config['directoryOrFileNotFound'] . PHP_EOL, $args[0]);
                break;
            }
            $targetDirectory = $args[0];
            if (!chdir($targetDirectory)) {
                fprintf(STDOUT, $config['directoryOrFileNotFound'] . PHP_EOL, $targetDirectory);
            }
            break;

        case $config['lookup']['type']:
            if (empty($args)) {
                printf($config['commandNotFound'] . PHP_EOL, $config['lookup']['type']);
                break;
            }

            $targetBashCommand = $args[0];
            if (isBuiltinCommand($targetBashCommand, $config['lookup'])) {
                printf($config['shellBuiltin'] . PHP_EOL, $targetBashCommand);
                break;
            }

            $executablePath = findExecutablePath($targetBashCommand, $config);
            if ($executablePath !== false) {
                printf($config['commandLocation'] . PHP_EOL, $targetBashCommand, $executablePath);
            } else {
                printf($config['commandNotFound'] . PHP_EOL, $targetBashCommand);
            }
            break;

        default:
            tryExecuteSystemCommand($command, $args, $config);
            break;
    }
}
function isBuiltinCommand(string $command, array $lookup): bool
{
    return in_array($command, $lookup);
}

function tryExecuteSystemCommand(string $command, array $args, array $config): void
{
    $fullCommand = $command . ' ' . implode(' ', array_map('escapeshellarg', $args));

    foreach ($config['path'] as $pathDir) {
        $fullPath = $pathDir . DIRECTORY_SEPARATOR . $command;
        if (is_executable($fullPath)) {
            $output = [];
            exec($fullCommand, $output);
            echo implode(PHP_EOL, $output) . PHP_EOL;
            return;
        }
    }
    printf($config['commandNotFound'] . PHP_EOL, $command);
}

function findExecutablePath(string $command, array $config): string|false
{
    $pathDirs = array_merge(['.'], $config['path']);

    foreach ($pathDirs as $dir) {
        $fullPath = $dir . DIRECTORY_SEPARATOR . $command;
        if (file_exists($fullPath) && is_executable($fullPath)) {
            return $fullPath;
        }
    }
    return false;
}

while (true) {
    $input = UserInputInBash($config['promptSymbol']);
    if ($input === '') {
        continue;
    }

    $parsedInput = parseQuotedArguments($input);
    $command = array_shift($parsedInput);
    $arguments = $parsedInput;

    executeCommand($command, $arguments, $config);
}

function parseQuotedArguments(string $inputString): array
{
    $resultParsedArguments = [];
    $currentWord = '';
    $isInsideSingleQuote = false;
    $isInsideDoubleQuote = false;
    $isEscapeActive = false;

    for ($currentIndex = 0; $currentIndex < strlen($inputString); $currentIndex++) {
        $currentChar = $inputString[$currentIndex];

        if ($isEscapeActive) {
            $currentWord .= $currentChar;
            $isEscapeActive = false;
            continue;
        }

        if ($currentChar === '\\') {
            $isEscapeActive = true;
            continue;
        }

        if ($currentChar === '"' && !$isInsideSingleQuote) {
            $isInsideDoubleQuote = !$isInsideDoubleQuote;
            continue;
        }

        if ($currentChar === "'" && !$isInsideDoubleQuote) {
            $isInsideSingleQuote = !$isInsideSingleQuote;
            continue;
        }

        if ($currentChar === ' ' && !$isInsideSingleQuote && !$isInsideDoubleQuote) {
            if ($currentWord !== '') {
                $resultParsedArguments[] = $currentWord;
                $currentWord = '';
            }
            continue;
        }

        $currentWord .= $currentChar;
    }

    if ($currentWord !== '') {
        $resultParsedArguments[] = $currentWord;
    }

    return $resultParsedArguments;
}
