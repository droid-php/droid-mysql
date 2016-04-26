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

class MysqlDumpAllCommand extends BaseMysqlDumpCommand
{
    public function configure()
    {
        $this->setName('mysql:dump-all')
            ->setDescription('Perform a mysql dump for all databases on a server')
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
        if (!$host) {
            throw new RuntimeException("Invalid URL format: " . $url);
        }
        $output->writeLn("Mysql Dump: Database $dbname on $host as $user to $dest");
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
            "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME !='information_schema';"
        );
        $command = $this->getApplication()->find('mysql:dump');
        foreach ($stmt->fetchAll() as $row) {
            $dbname = $row['SCHEMA_NAME'];
            echo " * " . $dbname . "\n";
            $input->setArgument('url', $url . '/' . $dbname);
            $input->setArgument('dest', str_replace('DBNAME', $dbname, $dest));
            $command->execute($input, $output);
        }
    }
}
