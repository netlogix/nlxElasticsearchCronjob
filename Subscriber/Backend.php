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
    private const ENABLED_CATEGORY_ACTIONS = ['addCategoryArticles', 'removeCategoryArticles'];
    private const ENABLED_MANUAL_SORTING_ACTIONS = ['assignPosition', 'removePosition', 'resetCategory'];
    private const ENABLED_ARTICLE_ACTIONS = ['setPropertyList', 'saveDetail', 'delete'];

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
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Category' => 'onBackendCategory',
            'Enlight_Controller_Action_PostDispatchSecure_Backend_ManualSorting' => 'onManualSorting',
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Article' => 'onBackendArticle',
            'Enlight_Controller_Front_DispatchLoopShutdown' => 'onTerminate',
        ];
    }

    public function onBackendCategory(\Enlight_Controller_ActionEventArgs $args): void
    {
        $actionName = $args->getRequest()->getActionName();

        if (false === $this->isFeatureEnabled || false === \in_array($actionName, self::ENABLED_CATEGORY_ACTIONS)) {
            return;
        }
        $this->syncBacklog = true;
    }

    public function onManualSorting(\Enlight_Controller_ActionEventArgs $args): void
    {
        $actionName = $args->getRequest()->getActionName();

        if (false === $this->isFeatureEnabled || false === \in_array($actionName, self::ENABLED_MANUAL_SORTING_ACTIONS)) {
            return;
        }
        $this->syncBacklog = true;
    }

    public function onBackendArticle(\Enlight_Controller_ActionEventArgs $args): void
    {
        $actionName = $args->getRequest()->getActionName();

        if (false === $this->isFeatureEnabled || false === \in_array($actionName, self::ENABLED_ARTICLE_ACTIONS)) {
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
