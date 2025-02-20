<?php

error_reporting(E_ALL);

$generalConfig = require 'config/general.php';
$systemConfig = require 'config/system.php';
$commandsConfig = require 'config/commands.php';

$config = array_merge($generalConfig, $systemConfig, $commandsConfig);


function getUserInput(string $prompt): string
{
    fwrite(STDOUT, $prompt);
    return trim(fgets(STDIN));
}

function executeShellCommand(string $commandName, array $commandArgs, array $appConfig): void
{
    switch ($commandName) {
        case $appConfig['lookup']['echo']:
            echo implode(' ', $commandArgs) . PHP_EOL;
            break;

        case $appConfig['lookup']['cat']:
            foreach ($commandArgs as $filePath) {
                if (!file_exists($filePath)) {
                    fprintf(STDOUT, $appConfig['directoryOrFileNotFound'] . PHP_EOL, $filePath);
                    return;
                }
            }
            runSystemCommand($commandName, $commandArgs, $appConfig);
            break;

        case $appConfig['lookup']['exit']:
            exit($appConfig['exitSuccess']);

        case $appConfig['lookup']['pwd']:
            echo getcwd() . PHP_EOL;
            break;

        case $appConfig['lookup']['cd']:
            if (empty($commandArgs)) {
                fprintf(STDOUT, $appConfig['directoryOrFileNotFound'] . PHP_EOL, '~');
                return;
            }
            if ($commandArgs[0] === '~') {
                $commandArgs[0] = $appConfig['home'];
            }
            if (!is_dir($commandArgs[0])) {
                fprintf(STDOUT, $appConfig['directoryOrFileNotFound'] . PHP_EOL, $commandArgs[0]);
                break;
            }
            $destinationPath = $commandArgs[0];
            if (!chdir($destinationPath)) {
                fprintf(STDOUT, $appConfig['directoryOrFileNotFound'] . PHP_EOL, $destinationPath);
            }
            break;

        case $appConfig['lookup']['type']:
            if (empty($commandArgs)) {
                printf($appConfig['commandNotFound'] . PHP_EOL, $appConfig['lookup']['type']);
                break;
            }

            $searchedCommand = $commandArgs[0];
            if (isShellBuiltin($searchedCommand, $appConfig['lookup'])) {
                printf($appConfig['shellBuiltin'] . PHP_EOL, $searchedCommand);
                break;
            }

            $executableFullPath = locateExecutable($searchedCommand, $appConfig);
            if ($executableFullPath !== false) {
                printf($appConfig['commandLocation'] . PHP_EOL, $searchedCommand, $executableFullPath);
            } else {
                printf($appConfig['commandNotFound'] . PHP_EOL, $searchedCommand);
            }
            break;

        default:
            runSystemCommand($commandName, $commandArgs, $appConfig);
            break;
    }
}

function isShellBuiltin(string $commandName, array $builtins): bool
{
    return in_array($commandName, $builtins);
}

function runSystemCommand(string $commandName, array $commandArgs, array $appConfig): void
{
    $formattedCommand = $commandName . ' ' . implode(' ', array_map('escapeshellarg', $commandArgs));

    foreach ($appConfig['path'] as $directory) {
        $fullCommandPath = $directory . DIRECTORY_SEPARATOR . $commandName;
        if (is_executable($fullCommandPath)) {
            $outputLines = [];
            exec($formattedCommand, $outputLines);
            echo implode(PHP_EOL, $outputLines) . PHP_EOL;
            return;
        }
    }
    printf($appConfig['commandNotFound'] . PHP_EOL, $commandName);
}

function locateExecutable(string $commandName, array $appConfig): string|false
{
    $searchDirectories = array_merge(['.'], $appConfig['path']);

    foreach ($searchDirectories as $directory) {
        $potentialPath = $directory . DIRECTORY_SEPARATOR . $commandName;
        if (file_exists($potentialPath) && is_executable($potentialPath)) {
            return $potentialPath;
        }
    }
    return false;
}

while (true) {
    $userInput = getUserInput($config['promptSymbol']);
    if ($userInput === '') {
        continue;
    }

    $parsedCommandInput = parseArguments($userInput);
    $commandName = array_shift($parsedCommandInput);
    $commandArgs = $parsedCommandInput;

    executeShellCommand($commandName, $commandArgs, $config);
}

function parseArguments(string $input): array
{
    $parsedArgs = [];
    $currentArg = '';
    $inSingleQuotes = false;
    $inDoubleQuotes = false;
    $isEscaped = false;

    for ($i = 0; $i < strlen($input); $i++) {
        $char = $input[$i];

        if ($isEscaped) {
            $currentArg .= $char;
            $isEscaped = false;
            continue;
        }

        if ($char === '\\') {
            $isEscaped = true;
            continue;
        }

        if ($char === '"' && !$inSingleQuotes) {
            $inDoubleQuotes = !$inDoubleQuotes;
            continue;
        }

        if ($char === "'" && !$inDoubleQuotes) {
            $inSingleQuotes = !$inSingleQuotes;
            continue;
        }

        if ($char === ' ' && !$inSingleQuotes && !$inDoubleQuotes) {
            if ($currentArg !== '') {
                $parsedArgs[] = $currentArg;
                $currentArg = '';
            }
            continue;
        }

        $currentArg .= $char;
    }

    if ($currentArg !== '') {
        $parsedArgs[] = $currentArg;
    }

    return $parsedArgs;
}
