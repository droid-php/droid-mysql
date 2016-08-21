<?php

namespace Droid\Plugin\Mysql\Command;

use RuntimeException;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Droid\Lib\Plugin\Command\CheckableTrait;
use Droid\Plugin\Mysql\Db\Client;
use Droid\Plugin\Mysql\Db\ClientException;
use Droid\Plugin\Mysql\Model\User;

class MysqlAdduserCommand extends Command
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
            ->setName('mysql:adduser')
            ->setDescription('Add a MySQL user account.')
            ->addArgument(
                'url',
                InputArgument::REQUIRED,
                'Connection url'
            )
            ->addArgument(
                'username',
                InputArgument::REQUIRED,
                'Create a user with this user name.'
            )
            ->addArgument(
                'password',
                InputArgument::REQUIRED,
                'Create a user having this password.'
            )
            ->addArgument(
                'allowed-host',
                InputArgument::REQUIRED,
                'Allow the user to connect from host (e.g. 127.0.0.1, any, etc.)'
            )
            ->addOption(
                'grant',
                null,
                InputOption::VALUE_REQUIRED,
                'Grant these privileges to the user'
            )
            ->addOption(
                'grant-level',
                null,
                InputOption::VALUE_REQUIRED,
                'Grant the privileges ON this level (e.g. *.*, dbname.*, dbname.tablename, etc.)'
            )
            ->addOption(
                'can-grant',
                null,
                InputOption::VALUE_NONE,
                'Grant the user to grant other users own privileges'
            )
        ;
        $this->configureCheckMode();
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('grant') && ! $input->getOption('grant-level')) {
            throw new RuntimeException(
                'You must specify --grant_level when using --grant.'
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
        $this->activateCheckMode($input);

        $user = new User($this->client);
        $user
            ->setName($input->getArgument('username'))
            ->setPassword($input->getArgument('password'))
            ->setHost($input->getArgument('allowed-host'))
            ->setGrants($input->getOption('grant'))
            ->setGrantLevel($input->getOption('grant-level'))
        ;
        if ($input->getOption('can-grant')) {
            $user->setCanGrant(true);
        }

        if ($user->exists()) {
            $output->writeln(
                sprintf(
                    'I will not create user "%s" @host "%s" because one already exists.',
                    $input->getArgument('username'),
                    $input->getArgument('allowed-host')
                )
            );
        } elseif ($this->checkMode()) {
            $this->markChange();
            $output->writeln(
                sprintf(
                    'I would create the user "%s".',
                    $input->getArgument('username')
                )
            );
        } else {
            $this->markChange();
            try {
                $user->create();
            } catch (ClientException $e) {
                throw new RuntimeException(
                    sprintf(
                        'I cannot create user "%s".',
                        $input->getArgument('username')
                    ),
                    null,
                    $e
                );
            }
            $output->writeln(
                sprintf(
                    'I have successfully created the user "%s".',
                    $input->getArgument('username')
                )
            );
        }

        if ($input->getOption('grant')) {
            $this->markChange();
            if ($this->checkMode()) {
                $output->writeln(
                    sprintf(
                        'I would grant privileges to the user "%s".',
                        $input->getArgument('username')
                    )
                );
            } else {
                try {
                    $user->grant();
                } catch (ClientException $e) {
                    throw new RuntimeException(
                        sprintf(
                            'I cannot grant privileges to the user "%s".',
                            $input->getArgument('username')
                        ),
                        null,
                        $e
                    );
                }
                $output->writeln(
                    sprintf(
                        'I have successfully granted privileges to the user "%s".',
                        $input->getArgument('username')
                    )
                );
            }
        }

        $this->reportChange($output);
        return 0;
    }
}
