<?php
declare(strict_types=1);

/*
 * Created by netlogix GmbH & Co. KG
 *
 * @copyright netlogix GmbH & Co. KG
 */

namespace nlxElasticsearchCronjob\Subscriber;

use Enlight\Event\SubscriberInterface;
use nlxElasticsearchCronjob\Services\BacklogSyncInterface;
use nlxElasticsearchCronjob\Services\Config;

class Backend implements SubscriberInterface
{
    private const ENABLED_BACKEND_ACTIONS = [
        'addCategoryArticles',
        'removeCategoryArticles',
        'assignPosition',
        'removePosition',
        'resetCategory',
        'saveDetail',
        'delete',
        'saveData',
    ];

    /** @var bool */
    private $isFeatureEnabled;

    /** @var BacklogSyncInterface */
    private $backlogSync;

    /** @var bool */
    private $syncBacklog = false;

    public function __construct(Config $config, BacklogSyncInterface $backlogSync)
    {
        $this->isFeatureEnabled = $config->isBacklogSyncFeatureEnabled();
        $this->backlogSync = $backlogSync;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Category' => 'onBackend',
            'Enlight_Controller_Action_PostDispatchSecure_Backend_ManualSorting' => 'onBackend',
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Article' => 'onBackend',
            'Enlight_Controller_Action_PostDispatchSecure_Backend_AttributeData' => 'onBackend',
            'Enlight_Controller_Front_DispatchLoopShutdown' => 'onTerminate',
        ];
    }

    public function onBackend(\Enlight_Controller_ActionEventArgs $args): void
    {
        $actionName = $args->getRequest()->getActionName();

        if (false === $this->isFeatureEnabled || false === in_array($actionName, self::ENABLED_BACKEND_ACTIONS)) {
            return;
        }
        $this->syncBacklog = true;
    }

    public function onTerminate(): void
    {
        if (false === $this->syncBacklog) {
            return;
        }
        $this->backlogSync->sync();
    }
}
