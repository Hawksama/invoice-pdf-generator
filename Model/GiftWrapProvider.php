<?php

namespace Hawksama\Invoice\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Hawksama\Invoice\Helper\Data;

class GiftWrapProvider implements ConfigProviderInterface
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @param Data $helper
     */
    public function __construct(
        Data $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $giftWrapProductId = $this->helper->getGiftWrapProductId();
        return [
            'giftWrapProductId' => $giftWrapProductId
        ];
    }
}
