<?php
declare(strict_types=1);

/*
 * Created by netlogix GmbH & Co. KG
 *
 * @copyright netlogix GmbH & Co. KG
 */

namespace nlxElasticsearchCronjob\Subscriber;

use Enlight\Event\SubscriberInterface;
use Psr\Log\LoggerInterface;
use Shopware\Components\Console\Application;
use Shopware\Kernel;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class Cronjob implements SubscriberInterface
{
    /** @var Kernel */
    private $kernel;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(Kernel $kernel, LoggerInterface $logger)
    {
        $this->kernel = $kernel;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_CronJob_nlxEsIndexPopulate' => 'onEsIndexPopulate',
            'Shopware_CronJob_nlxEsBacklogSync' => 'onEsBacklogSync',
        ];
    }

    public function onEsIndexPopulate(\Shopware_Components_Cron_CronJob $cronJob): void
    {
        $command = new Application($this->kernel);
        $command->setAutoExit(false);
        $command->setCatchExceptions(false);

        $input = new ArrayInput([
            'command' => 'sw:es:index:cleanup',
        ]);
        $this->runCommand($command, $input, 'The command "sw:es:index:cleanup" failed');

        $input = new ArrayInput([
            'command' => 'sw:es:index:populate',
        ]);
        $this->runCommand($command, $input, 'The command "sw:es:index:populate" failed');
    }

    public function onEsBacklogSync(\Shopware_Components_Cron_CronJob $cronJob): void
    {
        $command = new Application($this->kernel);
        $command->setAutoExit(false);
        $command->setCatchExceptions(false);

        $input = new ArrayInput([
            'command' => 'sw:es:backlog:sync',
        ]);
        $this->runCommand($command, $input, 'The command "sw:es:backlog:sync" failed');

        $input = new ArrayInput([
            'command' => 'sw:es:backlog:clear',
        ]);
        $this->runCommand($command, $input, 'The command "sw:es:backlog:clear" failed');
    }

    private function runCommand(Application $command, ArrayInput $input, string $errorMessage): void
    {
        try {
            $command->run($input, new NullOutput());
        } catch (\Throwable $exception) {
            echo $errorMessage;
            $this->logger->error(
                $errorMessage,
                [
                    'exception' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => $exception->getTraceAsString(),
                ]
            );
        }
    }
}
