<?php

namespace Hawksama\Invoice\Block\Order\Item\Renderer;

use Magento\Framework\Stdlib\StringUtils;
use Magento\Catalog\Model\Product\OptionFactory;
use Magento\Catalog\Block\Product\ImageBuilder;

class DefaultRenderer extends \Magento\Sales\Block\Order\Item\Renderer\DefaultRenderer
{
    /**
     * Magento string lib
     *
     * @var StringUtils
     */
    protected $string;

    /**
     * @var OptionFactory
     */
    protected $_productOptionFactory;

    /**
     * @var ImageBuilder
     */
    protected $imageBuilder;

    /**
     * @param  $context
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Magento\Catalog\Model\Product\OptionFactory $productOptionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        StringUtils $string,
        OptionFactory $productOptionFactory,
        ImageBuilder $imageBuilder,
        array $data = []
    ) {
        parent::__construct($context, $string, $productOptionFactory, $data);
        $this->imageBuilder = $imageBuilder;
    }

    /**
     * Get item product
     *
     * @return \Magento\Catalog\Model\Product
     * @codeCoverageIgnore
     */
    public function getProduct()
    {
        return $this->getItem()->getProduct();
    }

    /**
     * Identify the product from which thumbnail should be taken.
     *
     * @return \Magento\Catalog\Model\Product
     * @codeCoverageIgnore
     */
    public function getProductForThumbnail()
    {
        return $this->getProduct();
    }

    /**
     * Retrieve product image
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param string $imageId
     * @param array $attributes
     * @return \Magento\Catalog\Block\Product\Image
     */
    public function getImage($product, $imageId, $attributes = [])
    {
        return $this->imageBuilder->setProduct($product)
            ->setImageId($imageId)
            ->setAttributes($attributes)
            ->create();
    }
}