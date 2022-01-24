<?php
declare(strict_types=1);

/*
 * Created by netlogix GmbH & Co. KG
 *
 * @copyright netlogix GmbH & Co. KG
 */

namespace nlxElasticsearchCronjob\Services;

use Shopware\Bundle\ESIndexingBundle\BacklogProcessorInterface;
use Shopware\Bundle\ESIndexingBundle\BacklogReaderInterface;
use Shopware\Bundle\ESIndexingBundle\IdentifierSelector;
use Shopware\Bundle\ESIndexingBundle\IndexFactory;
use Shopware\Bundle\ESIndexingBundle\MappingInterface;
use Shopware\Bundle\ESIndexingBundle\Struct\Backlog;
use Traversable;

/**
 * This class is inspired by Shopware\Bundle\ESIndexingBundle\Commands\BacklogSyncCommand
 */
class BacklogSync implements BacklogSyncInterface
{
    /** @var BacklogReaderInterface */
    private $backlogReader;

    /** @var IdentifierSelector */
    private $identifierSelector;

    /** @var IndexFactory */
    private $indexFactory;

    /** @var BacklogProcessorInterface */
    private $backlogProcessor;

    /** @var MappingInterface[] */
    private $mappings;

    /** @var int */
    private $batchSize;

    public function __construct(
        BacklogReaderInterface $backlogReader,
        IdentifierSelector $identifierSelector,
        IndexFactory $indexFactory,
        BacklogProcessorInterface $backlogProcessor,
        Traversable $mappings,
        int $batchSize
    ) {
        $this->backlogReader = $backlogReader;
        $this->identifierSelector = $identifierSelector;
        $this->indexFactory = $indexFactory;
        $this->backlogProcessor = $backlogProcessor;
        $this->batchSize = $batchSize;
        $this->mappings = \iterator_to_array($mappings, false);
    }

    public function sync(): void
    {
        $lastBackLogId = $this->backlogReader->getLastBacklogId();
        $backlogs = $this->backlogReader->read($lastBackLogId, $this->batchSize);

        if (empty($backlogs)) {
            return;
        }

        /** @var Backlog $last */
        $last = $backlogs[\count($backlogs) - 1];
        $this->backlogReader->setLastBacklogId($last->getId());
        $shops = $this->identifierSelector->getShops();

        foreach ($shops as $shop) {
            foreach ($this->mappings as $mapping) {
                $type = $mapping->getType();
                $index = $this->indexFactory->createShopIndex($shop, $type);

                $this->backlogProcessor->process($index, $backlogs);
            }
        }
    }
}
