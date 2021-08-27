<?php

namespace Hawksama\Invoice\Block\Adminhtml\Items\Column;

/**
 * Invoice Order items name column renderer
 */
class Name extends \Magento\Sales\Block\Adminhtml\Items\Column\Name
{
    /**
     * Image helper instance
     *
     * @var \Magento\Catalog\Helper\Image $imageHelper
     */
    protected $imageHelper;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Catalog\Model\Product\OptionFactory $optionFactory
     * @param \Magento\Catalog\Helper\ImageFactory $imageHelperFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\Product\OptionFactory $optionFactory,
        \Magento\Catalog\Helper\ImageFactory $imageHelperFactory,
        array $data = []
    ) {
        $this->imageHelper = $imageHelperFactory->create();

        parent::__construct($context, $stockRegistry, $stockConfiguration, $registry, $optionFactory, $data);
    }

    /**
     * Get item thumbnail image
     *
     * @param \Magento\Sales\Model\Order\Item $item
     * @return string
     */
    public function getItemImage($item): string
    {
        if ($item->getProduct()) {
            return $this->imageHelper->init($item->getProduct(), 'product_thumbnail_image')->getUrl();
        }

        return '';
    }
}
