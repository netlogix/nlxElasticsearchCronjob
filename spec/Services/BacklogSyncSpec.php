<?php
declare(strict_types=1);

/*
 * Created by netlogix GmbH & Co. KG
 *
 * @copyright netlogix GmbH & Co. KG
 */

namespace spec\nlxElasticsearchCronjob\Services;

use Doctrine\Common\Collections\ArrayCollection;
use nlxElasticsearchCronjob\Services\BacklogSync;
use nlxElasticsearchCronjob\Services\BacklogSyncInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Shopware\Bundle\ESIndexingBundle\BacklogProcessorInterface;
use Shopware\Bundle\ESIndexingBundle\BacklogReaderInterface;
use Shopware\Bundle\ESIndexingBundle\IdentifierSelector;
use Shopware\Bundle\ESIndexingBundle\IndexFactory;
use Shopware\Bundle\ESIndexingBundle\Product\ProductMapping;
use Shopware\Bundle\ESIndexingBundle\Property\PropertyMapping;
use Shopware\Bundle\ESIndexingBundle\Struct\Backlog;
use Shopware\Bundle\ESIndexingBundle\Struct\ShopIndex;
use Shopware\Bundle\StoreFrontBundle\Struct\Shop;

class BacklogSyncSpec extends ObjectBehavior
{
    const BACTH_SIZE = 100;

    public function let(
        BacklogReaderInterface $backlogReader,
        IdentifierSelector $identifierSelector,
        IndexFactory $indexFactory,
        BacklogProcessorInterface $backlogProcessor,
        ProductMapping $productMapping,
        PropertyMapping $propertyMapping
    ): void {
        $mapping = new ArrayCollection([$productMapping->getWrappedObject(), $propertyMapping->getWrappedObject()]);

        $this->beConstructedWith(
            $backlogReader,
            $identifierSelector,
            $indexFactory,
            $backlogProcessor,
            $mapping,
            self::BACTH_SIZE
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(BacklogSync::class);
    }

    public function it_implements_ArticleDataProcessor_interface(): void
    {
        $this->shouldImplement(BacklogSyncInterface::class);
    }

    public function it_should_sync(
        BacklogReaderInterface $backlogReader,
        IdentifierSelector $identifierSelector,
        IndexFactory $indexFactory,
        BacklogProcessorInterface $backlogProcessor,
        ProductMapping $productMapping,
        PropertyMapping $propertyMapping,
        Backlog $backlog,
        Shop $shop,
        ShopIndex $productIndex,
        ShopIndex $propertyIndex
    ): void {
        $lastBacklogId = 1;

        $backlogReader->getLastBacklogId()
            ->willReturn($lastBacklogId);

        $backlogReader->read($lastBacklogId, self::BACTH_SIZE)
            ->willReturn([$backlog]);

        $backlog->getId()
            ->willReturn(2);

        $backlogReader->setLastBacklogId(2)
            ->shouldBeCalled();

        $identifierSelector->getShops()
            ->willReturn([$shop]);

        $productMapping->getType()
            ->willReturn('product');
        $propertyMapping->getType()
            ->willReturn('property');

        $indexFactory->createShopIndex($shop, 'product')
            ->willReturn($productIndex);
        $indexFactory->createShopIndex($shop, 'property')
            ->willReturn($propertyIndex);

        $backlogProcessor->process($productIndex, [$backlog])
            ->shouldBeCalled();
        $backlogProcessor->process($propertyIndex, [$backlog])
            ->shouldBeCalled();

        $this->sync();
    }

    public function it_do_nothon_if_backlogs_empty(
        BacklogReaderInterface $backlogReader,
        IdentifierSelector $identifierSelector,
        IndexFactory $indexFactory,
        BacklogProcessorInterface $backlogProcessor,
        ProductMapping $productMapping,
        PropertyMapping $propertyMapping
    ): void {
        $lastBacklogId = 1;

        $backlogReader->getLastBacklogId()
            ->willReturn($lastBacklogId);

        $backlogReader->read($lastBacklogId, self::BACTH_SIZE)
            ->willReturn([]);

        $backlogReader->setLastBacklogId(Argument::any())
            ->shouldNotBeCalled();

        $identifierSelector->getShops()
            ->shouldNotBeCalled();

        $productMapping->getType()
            ->shouldNotBeCalled();
        $propertyMapping->getType()
            ->shouldNotBeCalled();

        $indexFactory->createShopIndex(Argument::any(), Argument::any())
            ->shouldNotBeCalled();

        $backlogProcessor->process(Argument::any(), Argument::any())
            ->shouldNotBeCalled();

        $this->sync();
    }
}
