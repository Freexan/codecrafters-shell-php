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
    'echo'=> function ($arguments, $commandsList) {
        echo implode(' ', $arguments) . PHP_EOL;
    },
    'exit' => function ($arguments, $commandsList) {
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
        } else {
            echo "$target_command: not found" . PHP_EOL;
        }
    },

];

while (true) {
    $input = promptUserInput();
    processCommand($input, $commandsList);
}
