<?php

error_reporting(E_ALL);

// Shell prompt
const PROMPT_SYMBOL = '$ ';

// Command status messages
const COMMAND_NOT_FOUND = '%s: not found';
const SHELL_BUILTIN = '%s is a shell builtin';
const COMMAND_LOCATION = '%s is %s';
const EXIT_SUCCESS = 0;

/**
 * Displays a prompt and reads user input.
 */
function promptUserInput(): string
{
    fwrite(STDOUT, PROMPT_SYMBOL);
    return trim(fgets(STDIN));
}

/**
 * Processes user input by matching it
 * to available commands.
 * Executes the matching command or
 * prints an error if not found.
 *
 * @param string $input User's command
 * @param array $commandsList List of
 * available commands (name => callback).
 * @return void
 */
function processCommand(string $input, array $commandsList): void
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
        $paths = getPath();

        foreach ($paths as $path) {
            $fullPath = $path . DIRECTORY_SEPARATOR . $command;
            if (is_executable($fullPath)) {
                $output = [];
                // Use the original command name in the exec call
                exec($input, $output);
                echo implode(PHP_EOL, $output) . PHP_EOL;
                return;
            }
        }

        printf(COMMAND_NOT_FOUND . PHP_EOL, $command);
    }
}

/**
 * A list of available commands and their implementations.
 */
$commandsList = [
    'echo' => function (array $arguments): void {
        echo implode(' ', $arguments) . PHP_EOL;
    },
    'exit' => function () use (&$commandsList): never {
        exit(EXIT_SUCCESS);
    },
    'pwd' => function () use (&$commandsList): void {
        print getcwd() . PHP_EOL;
    },
    'type' => function (array $arguments, array $commandsList): void {
        if (empty($arguments)) {
            printf(COMMAND_NOT_FOUND . PHP_EOL, 'type');
        }
        $targetCommand = $arguments[0];

        if (isset($commandsList[$targetCommand])) {
            printf(SHELL_BUILTIN . PHP_EOL, $targetCommand);
            return;
        }
        $executablePath = findExecutablePath($targetCommand);
        if ($executablePath !== false) {
            printf(COMMAND_LOCATION . PHP_EOL, $targetCommand, $executablePath);
        } else {
            printf(COMMAND_NOT_FOUND . PHP_EOL, $targetCommand);
        }
    },
];

/**
 * Retrieves the system PATH as an array
 * of directories.
 *
 * @return array An array of directories
 * from the PATH environment variable.
 */
function getPath(): array
{
    return explode(PATH_SEPARATOR, getenv('PATH'));
}

/**
 * Finds the full executable path for a
 * given command, if it is executable.
 *
 * @param string $command The command to
 * search for.
 * @return string|false The executable
 * path if found, or false if not.
 */
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

// Main loop
while (true) {
    $input = promptUserInput();
    processCommand($input, $commandsList);
}
