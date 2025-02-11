<?php

error_reporting(E_ALL);

function promptUserInput()
{
    fwrite(STDOUT, "$ ");
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
        printf("%s: command not found\n", $input);
    }
}


$commandsList = [
    'echo'=> function ($arguments) {
        echo implode(' ', $arguments) . PHP_EOL;
    },
    'exit' => function () {
        exit(0);
    },
    'type' => function ($arquments, $commandsList) {
        if (empty($arquments)) {
            echo "invalid_command: not found" . PHP_EOL;
            return;
        }
        $target_command = $arquments[0];

        if (isset($commandsList[$target_command])){
            echo "$target_command is a shell builtin" . PHP_EOL;
            return;
        }

                $executablePath = findExecutablePath($target_command);
                if ($executablePath) {
                    echo "$target_command is $executablePath" . PHP_EOL;
                    return;
                }
                    echo "$target_command: not found" . PHP_EOL;
    },
];

function getPath()
{
    return explode(PATH_SEPARATOR, getenv('PATH'));
}
function findExecutablePath($command) {
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
