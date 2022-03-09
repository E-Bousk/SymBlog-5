<?php

namespace App\CustomConsole;

use Symfony\Component\Console\Command\Command;
use Symfony\Bundle\FrameworkBundle\Console\Application;

class CustomConsole extends Application
{
    /**
     * Note : override add() method present in line 153 of Application class.
     * 
     * @param Command $command 
     * @return null|Command - The registered command if enabled or null
     */
    public function add(Command $command): ?Command
    {
        if (
            $command->getName() === 'list'
            || str_starts_with($command->getName(), 'app:') === True
        ) {
            return parent::add($command);
        }

        return null;
    }
}
