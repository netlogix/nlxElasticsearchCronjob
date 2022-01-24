<?php
declare(strict_types=1);

/*
 * Created by netlogix GmbH & Co. KG
 *
 * @copyright netlogix GmbH & Co. KG
 */

namespace nlxElasticsearchCronjob\Services;

use Shopware\Components\Plugin\Configuration\ReaderInterface;

class Config implements ConfigInterface
{
    const PLUGIN_NAME = 'nlxElasticsearchCronjob';

    /** @var mixed[] */
    protected $config;

    public function __construct(ReaderInterface $configReader)
    {
        $this->config = $configReader->getByPluginName(self::PLUGIN_NAME);
    }

    public function isBacklogSyncFeatureEnabled(): bool
    {
        return $this->config['backlogSyncFeature'];
    }
}
