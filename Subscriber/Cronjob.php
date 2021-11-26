<?php
declare(strict_types=1);

/*
 * Created by netlogix GmbH & Co. KG
 *
 * @copyright netlogix GmbH & Co. KG
 */

namespace nlxElasticsearchCronjob\Subscriber;

use Enlight\Event\SubscriberInterface;
use Shopware\Components\Console\Application;
use Shopware\Kernel;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class Cronjob implements SubscriberInterface
{
    /** @var Kernel */
    private $kernel;

    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
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

        $input = new ArrayInput([
            'command' => 'sw:es:index:cleanup',
        ]);
        $command->run($input, new NullOutput());

        $input = new ArrayInput([
            'command' => 'sw:es:index:populate',
        ]);
        $command->run($input, new NullOutput());
    }

    public function onEsBacklogSync(\Shopware_Components_Cron_CronJob $cronJob): void
    {
        $command = new Application($this->kernel);
        $command->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'sw:es:backlog:sync',
        ]);
        $command->run($input, new NullOutput());

        $input = new ArrayInput([
            'command' => 'sw:es:backlog:cleanup',
        ]);
        $command->run($input, new NullOutput());
    }
}
