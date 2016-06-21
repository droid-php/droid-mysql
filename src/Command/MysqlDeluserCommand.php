<?php

namespace Droid\Plugin\Mysql\Command;

use RuntimeException;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Droid\Lib\Plugin\Command\CheckableTrait;
use Droid\Plugin\Mysql\Db\Client;
use Droid\Plugin\Mysql\Db\ClientException;
use Droid\Plugin\Mysql\Model\User;

class MysqlDeluserCommand extends Command
{
    use CheckableTrait;

    protected $client;

    public function __construct(Client $client, $name = null)
    {
        $this->client = $client;
        parent::__construct($name);
    }

    public function configure()
    {
        $this
            ->setName('mysql:deluser')
            ->setDescription('Delete a MySQL user account.')
            ->addArgument(
                'url',
                InputArgument::REQUIRED,
                'Connection url'
            )
            ->addArgument(
                'username',
                InputArgument::REQUIRED,
                'Delete the user with this user name.'
            )
            ->addArgument(
                'host',
                InputArgument::REQUIRED,
                'Delete the user allowed to connect from this host (e.g. 127.0.0.1, any, etc.)'
            )
        ;
        $this->configureCheckMode();
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this
            ->client
            ->getConfig()
            ->setConnectionUrl($input->getArgument('url'))
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->activateCheckMode($input);

        $user = new User($this->client);
        $user
            ->setName($input->getArgument('username'))
            ->setHost($input->getArgument('host'))
        ;

        if (!$user->exists()) {
            $output->writeln(
                sprintf(
                    'I will not delete user "%s" @host "%s" because one does not exist.',
                    $input->getArgument('username'),
                    $input->getArgument('host')
                )
            );
        } elseif ($this->checkMode()) {
            $this->markChange();
            $output->writeln(
                sprintf(
                    'I would delete the user "%s" @host "%s".',
                    $input->getArgument('username'),
                    $input->getArgument('host')
                )
            );
        } else {
            $this->markChange();
            try {
                $user->delete();
            } catch (ClientException $e) {
                throw new RuntimeException(
                    sprintf(
                        'I cannot delete user "%s" @host "%s".',
                        $input->getArgument('username'),
                        $input->getArgument('host')
                    ),
                    null,
                    $e
                );
            }
            $output->writeln(
                sprintf(
                    'I have successfully deleted the user "%s" @host "%s".',
                    $input->getArgument('username'),
                    $input->getArgument('host')
                )
            );
        }

        $this->reportChange($output);
        return 0;
    }
}
