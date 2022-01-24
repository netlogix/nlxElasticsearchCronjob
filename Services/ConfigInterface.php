<?php
declare(strict_types=1);

/*
 * Created by netlogix GmbH & Co. KG
 *
 * @copyright netlogix GmbH & Co. KG
 */

namespace nlxElasticsearchCronjob\Services;

interface ConfigInterface
{
    public function isBacklogSyncFeatureEnabled(): bool;
}
