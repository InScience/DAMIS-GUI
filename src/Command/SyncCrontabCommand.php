<?php

namespace App\Command;

use Base\MainBundle\Service\CronTabManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:crontab:sync',
    description: 'Writes the enabled cron jobs (admin page /cron) into the crontab of the current user.',
)]
class SyncCrontabCommand extends Command
{
    public function __construct(private readonly CronTabManager $cronTabManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->cronTabManager->syncToSystemCrontab()) {
            $output->writeln('<error>'.$this->cronTabManager->getLastError().'</error>');

            return Command::FAILURE;
        }
        $output->writeln('Crontab synchronised with the cron jobs in the database.');

        return Command::SUCCESS;
    }
}
