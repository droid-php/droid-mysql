<?php

namespace Droid\Plugin\Mysql\Command;

use RuntimeException;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Droid\Plugin\Mysql\Db\Client;
use Droid\Plugin\Mysql\Db\ClientException;
use Droid\Plugin\Mysql\Model\MasterInfo;

class MysqlMasterInfoCommand extends Command
{
    protected $client;

    public function __construct(Client $client, $name = null)
    {
        $this->client = $client;
        parent::__construct($name);
    }

    public function configure()
    {
        $this
            ->setName('mysql:master-info')
            ->setDescription('Configure a MySQL replication slave with information about the master.')
            ->addArgument(
                'url',
                InputArgument::REQUIRED,
                'Connection url.'
            )
            ->addArgument(
                'master_hostname',
                InputArgument::REQUIRED,
                'Host name or address of the Replication Master.'
            )
            ->addArgument(
                'replication_username',
                InputArgument::REQUIRED,
                'The name of the user account which will perform replication.'
            )
            ->addArgument(
                'replication_password',
                InputArgument::REQUIRED,
                'Password for the replication user.'
            )
            ->addOption(
                'log-name',
                'L',
                InputOption::VALUE_REQUIRED,
                'Recorded binary log file name. Can be omitted when the master has not yet been started with biary logging enabled.'
            )
            ->addOption(
                'log-position',
                'P',
                InputOption::VALUE_REQUIRED,
                'Recorded binary log file position. Can be omitted when the master has not yet been started with biary logging enabled.'
            )
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $usingLogName = $input->getOption('log-name') ? 1 : 0;
        $usingLogPos = $input->getOption('log-position') ? 1 : 0;
        if (($usingLogName + $usingLogPos) === 1) {
            throw new RuntimeException(
                'You must specify both (or neither) --log-name and --log-position.'
            );
        }

        $this
            ->client
            ->getConfig()
            ->setConnectionUrl($input->getArgument('url'))
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $masterInfo = new MasterInfo($this->client);

        $masterInfo
            ->setMasterHostname($input->getArgument('master_hostname'))
            ->setReplicationUsername($input->getArgument('replication_username'))
            ->setReplicationPassword($input->getArgument('replication_password'))
        ;
        if ($input->getOption('log-name')) {
            $masterInfo->setRecordedLogInfo(
                $input->getOption('log-name'),
                $input->getOption('log-position')
            );
        }

        $success = false;
        try {
            $success = $masterInfo->execute();
        } catch (ClientException $e) {
            throw new RuntimeException(
                'I cannot execute the CHANGE MASTER query.',
                null,
                $e
            );
        }
        if (!$success) {
            $output->writeln('I cannot configure the slave with the master information.');
            return 1;
        }

        $output->writeln('I have successfully configured the slave with the master information.');
        return 0;
    }
}
