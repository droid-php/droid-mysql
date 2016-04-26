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

class MysqlDumpCommand extends BaseMysqlDumpCommand
{
    public function configure()
    {
        $this->setName('mysql:dump')
            ->setDescription('Perform a mysql dump')
            ->addArgument(
                'url',
                InputArgument::REQUIRED,
                'Connection url'
            )
            ->addArgument(
                'dest',
                InputArgument::REQUIRED,
                'Destination filename'
            )
        ;
        parent::configure();
    }
    
    private function executableExist($cmd)
    {
        $returnVal = shell_exec("which $cmd");
        return (empty($returnVal) ? false : true);
    }
    
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $url = $input->getArgument('url');
        $dest = $input->getArgument('dest');
        
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $user = parse_url($url, PHP_URL_USER);
        $pass = parse_url($url, PHP_URL_PASS);
        $host = parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT);
        $dbname = trim(parse_url($url, PHP_URL_PATH), '/');
        if (!$port) {
            $port = 3306;
        }
        if (!$dbname) {
            throw new RuntimeException("Invalid URL format: " . $url);
        }
        $output->writeLn("Mysql Dump: Database $dbname on $host as $user to $dest");
        
        if (file_exists($dest)) {
            if (!$input->getOption('overwrite')) {
                throw new RuntimeException("Destination file already exists: " . $dest);
            }
            unlink($dest);
            if (file_exists($dest)) {
                throw new RuntimeException("Unlinking destination file failed: " . $dest);
            }
        }
        
        $bin = 'mysqldump';
        if (!$this->executableExist($bin)) {
            throw new RuntimeException("$bin executable not in path");
        }
        $cmd = $bin;
        if ($user) {
            $cmd .= ' --user=' . $user;
        }
        if ($host) {
            $cmd .= ' --host=' . $host;
        }
        if ($pass) {
            $cmd .= ' --password=' . $pass;
        }

        $passThroughs = [
            'compress',
            'force',
            'disable-keys',
            'quick',
            'add-locks'
        ];
        foreach ($passThroughs as $passThrough) {
            if ($input->getOption($passThrough)) {
                $cmd .= ' --' . $passThrough;
            }
        }

        $ignoreTables = $input->getOption('ignore-tables');
        if ($ignoreTables) {
            $tables = explode(',', $ignoreTables);
            foreach ($tables as $table) {
                $cmd .= ' --ignore-table=' . $dbname . '.' . $table;
            }
        }

        $cmd .= ' ' . $dbname;

        if ($input->getOption('bzip2')) {
            $cmd .= ' | bzip2';
        }
        if ($input->getOption('gzip')) {
            $cmd .= ' | gzip';
        }
        $cmd .= ' > ' . $dest;
        
        
        $process = new Process($cmd);
        $output->writeLn(str_replace($pass, '***', $process->getCommandLine()));
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
        
        if (!file_exists($dest)) {
            throw new RuntimeException("Destination file was not created");
        }
        
        $size = filesize($dest);
        if ($size <=0) {
            throw new RuntimeException("Destination filesize invalid: " . $size . ' bytes');
        }
        $output->writeLn("Destination filesize: " . $size . ' bytes');
    }
}
