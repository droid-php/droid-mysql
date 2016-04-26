<?php

namespace Droid\Plugin\Mysql\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Process\Exception\ProcessFailedException;
use RuntimeException;

abstract class BaseMysqlDumpCommand extends Command
{
    public function configure()
    {
        $this
            ->addOption(
                'gzip',
                'g',
                InputOption::VALUE_NONE,
                'Compress output with gzip'
            )
            ->addOption(
                'bzip2',
                'b',
                InputOption::VALUE_NONE,
                'Compress output with bzip2'
            )
            ->addOption(
                'compress',
                'C',
                InputOption::VALUE_NONE,
                'Compress all information sent between the client and the server if both support compression.'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Continue even if an SQL error occurs during a table dump'
            )
            ->addOption(
                'disable-keys',
                'K',
                InputOption::VALUE_NONE,
                'This makes loading the dump file faster because the indexes are created after all rows are inserted.'
            )
            ->addOption(
                'quick',
                null,
                InputOption::VALUE_NONE,
                'This option is useful for dumping large tables. ' .
                'It forces mysqldump to retrieve rows for a table from the server a row at a time ' .
                'rather than retrieving the entire row set and buffering it in memory before writing it out.'
            )
            ->addOption(
                'add-locks',
                null,
                InputOption::VALUE_NONE,
                'This results in faster inserts when the dump file is reloaded.'
            )
            ->addOption(
                'lock-tables',
                'l',
                InputOption::VALUE_NONE,
                'For each dumped database, lock all tables to be dumped before dumping them. ' .
                'The tables are locked with READ LOCAL to permit concurrent inserts in the case of MyISAM tables'
            )
            ->addOption(
                'lock-all-tables',
                'x',
                InputOption::VALUE_NONE,
                'Lock all tables across all databases. ' .
                'This is achieved by acquiring a global read lock for the duration of the whole dump.'
            )
            ->addOption(
                'single-transaction',
                null,
                InputOption::VALUE_NONE,
                'Dumps the consistent state of the database at the time when ' .
                'START TRANSACTION was issued without blocking any applications.'
            )
            ->addOption(
                'ignore-tables',
                null,
                InputOption::VALUE_REQUIRED,
                'Comma seperated list of tablenames to exclude from the dump'
            )
            ->addOption(
                'overwrite',
                'o',
                InputOption::VALUE_NONE,
                'Overwrite destination file in case it already exists'
            )
        ;
    }
}
