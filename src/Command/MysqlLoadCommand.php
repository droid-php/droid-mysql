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
use PDO;

class MysqlLoadCommand extends Command
{
    public function configure()
    {
        $this->setName('mysql:load')
            ->setDescription('Load a mysql dump')
            ->addArgument(
                'url',
                InputArgument::REQUIRED,
                'Connection url'
            )
            ->addArgument(
                'source',
                InputArgument::REQUIRED,
                'Source filename'
            )
            ->addOption(
                'gzip',
                'g',
                InputOption::VALUE_NONE,
                'Decompress source through gzip'
            )
            ->addOption(
                'bzip2',
                'b',
                InputOption::VALUE_NONE,
                'Decompress source through bzip2'
            )
            ->addOption(
                'create',
                'c',
                InputOption::VALUE_NONE,
                'Create database on server if it does not yet exist'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Drop the database before loading if it already exists'
            )
        ;
    }
    
    private function executableExist($cmd)
    {
        $returnVal = shell_exec("which $cmd");
        return (empty($returnVal) ? false : true);
    }
    
    private function getPdoFromUrl($url)
    {
        
    }
    
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $url = $input->getArgument('url');
        $source = $input->getArgument('source');
        
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

        $dsn = sprintf(
            '%s:host=%s;port=%d',
            $scheme,
            $host,
            $port
        );

        try {
            $pdo = new PDO($dsn, $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (\Exception $e) {
            throw new RuntimeException("Can't connect to mysql server with given url");
        }

        // Check if the database already exists and has tables
        $stmt = $pdo->query(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '" . $dbname . "'"
        );
        if ((bool)$stmt->fetchColumn()) {
            if (!$input->getOption('force')) {
                throw new RuntimeException("The database already exists and has tables");
            }
            $stmt = $pdo->query(
                "DROP DATABASE " . $dbname . ";"
            );
            $input->setOption('create', true);
        }
        
        // Check if the database exists, and if not, create if --create
        $stmt = $pdo->query(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . $dbname . "'"
        );
        if (!(bool)$stmt->fetchColumn()) {
            if (!$input->getOption('create')) {
                throw new RuntimeException("Database $dbname does not exist on server $host");
            }
            $stmt = $pdo->query(
                "CREATE DATABASE " . $dbname . ";"
            );
        }
        

        
        
        $output->writeLn("Mysql Load: Database $source into $dbname on $host as $user");
        
        if (!file_exists($source)) {
            throw new RuntimeException("Source file not found: " . $source);
        }
        
        $bin = 'mysql';
        if (!$this->executableExist($bin)) {
            throw new RuntimeException("$bin executable not in path");
        }
        
        if ($input->getOption('bzip2')) {
            $cmd = 'bunzip2 -c < ' . $source;
            //TODO: verify if source is a bzip2 file
        } elseif ($input->getOption('gzip')) {
            $cmd = 'zcat ' . $source;
            //TODO: verify if source is a gzip file
        } else {
            $cmd = 'cat ' . $source;
        }

        $cmd .= ' | ' . $bin;
        if ($user) {
            $cmd .= ' --user=' . $user;
        }
        if ($host) {
            $cmd .= ' --host=' . $host;
        }
        if ($pass) {
            $cmd .= ' --password=' . $pass;
        }

        $cmd .= ' ' . $dbname;
        
        $process = new Process($cmd);
        $output->writeLn(str_replace($pass, '***', $process->getCommandLine()));
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
        
        $stmt = $pdo->query(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '" . $dbname . "'"
        );
        if (!(bool)$stmt->fetchColumn()) {
            throw new RuntimeException("No tables exist after load");
        }
    }
}
