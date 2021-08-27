<?php

namespace Hawksama\Invoice\Block\Adminhtml;

use Magento\Framework\View\Element\Template\Context;
use Magento\SalesSequence\Model\ResourceModel\Meta as MetaResourceModel;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\DataObjectFactory;
use Magento\Tax\Api\TaxCalculationInterface;
use Hawksama\Invoice\Helper\Data;
use Magento\Directory\Model\RegionFactory;
use Magento\Tax\Helper\Data as TaxHelper;
use Magento\Tax\Model\Config;
use Magento\Catalog\Helper\Data as CatalogHelper;
use Magento\Tax\Model\Calculation;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * AbstractPdf class
 */
class AbstractPdf extends \Magento\Framework\View\Element\Template
{
    const VAT_PERCENTAGE = 'vat_percentage';
    const DEFAULT_VAT_PERCENTAGE = 19;

    const MAXIMUM_PRODUCTS_ON_PAGE = 20;

    /**
     * @var MetaResourceModel
     */
    protected $metaResourceModel;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var DataObjectFactory
     */
    protected $dataObjectFactory;

    /**
     * @var TaxCalculationApi
     */
    protected $taxCalculationApi;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var RegionFactory
     */
    protected $regionFactory;

    /**
     * @var TaxHelper
     */
    protected $taxHelper;

    /**
     * @var Config
     */
    protected $taxConfig;

    /**
     * @var Calculation
     */
    protected $calculationTool;

    /**
     * @var Filesystem
     */
    protected $fileSystem;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    public function __construct(
        Context $context,
        MetaResourceModel $metaResourceModel,
        DateTime $dateTime,
        DataObjectFactory $dataObjectFactory,
        TaxCalculationInterface $taxCalculationApi,
        Data $helper,
        RegionFactory $regionFactory,
        TaxHelper $taxHelper,
        Config $taxConfig,
        CatalogHelper $catalogHelper,
        Calculation $calculationTool,
        Filesystem $fileSystem,
        PriceCurrencyInterface $priceCurrency,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->metaResourceModel = $metaResourceModel;
        $this->dateTime = $dateTime;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->taxCalculationApi = $taxCalculationApi;
        $this->helper = $helper;
        $this->regionFactory = $regionFactory;
        $this->taxHelper = $taxHelper;
        $this->taxConfig = $taxConfig;
        $this->catalogHelper = $catalogHelper;
        $this->calculationTool = $calculationTool;
        $this->fileSystem = $fileSystem;
        $this->priceCurrency = $priceCurrency;
    }

    public function getEntityPrefix($entity)
    {
        return $this->metaResourceModel->loadByEntityTypeAndStore(
            $entity->getEntityType(),
            $entity->getStoreId()
        )
            ->getActiveProfile()
            ->getPrefix();
    }

    public function getShippingItem($entity, $order)
    {
        /* if ($entity->getEntityType() == 'creditmemo' && !($entity->getShippingInclTax() > 0)) {
            return false;
        } */
        $shippingItem = $this->dataObjectFactory->create();
        $shippingPseudoProduct = $this->dataObjectFactory->create();
        $shippingPseudoOrderItem = $this->dataObjectFactory->create();

        $shippingTaxClassId = $this->taxConfig->getShippingTaxClass();
        if ($shippingTaxClassId) {
            $shippingPseudoProduct->setData('tax_class_id', $shippingTaxClassId);
        }

        $shippingBarCode = $this->helper->getConfigValue(\Hawksama\Invoice\Helper\Data::XML_PATH_SHIPPING_BAR_CODE);
        $shippingName = __('Shipping');
        if ($shippingBarCode) {
            $shippingName = sprintf("%s %s", $shippingBarCode, $shippingName);
        }

        $shippingItem->setData('name', $shippingName)
            ->setData('sku', $order->getShippingMethod())
            ->setData('qty', 1)
            ->setData('base_price', $entity->getShippingAmount())
            ->setData('price', $entity->getShippingAmount())
            ->setData('row_total', $entity->getShippingAmount())
            ->setData('row_total_incl_tax', $entity->getShippingInclTax())
            ->setData('tax_amount', $entity->getShippingTaxAmount());

        $shippingPseudoOrderItem->setData('product', $shippingPseudoProduct);
        $shippingItem->setData('order_item', $shippingPseudoOrderItem);

        return $shippingItem;
    }

    public function getDiscountEntityItem($entity, $negativeAmount = true)
    {
        if ($entity->getDiscountAmount() == 0) {
            return false;
        }
        $voucherBarCode = $this->helper->getConfigValue(\Hawksama\Invoice\Helper\Data::XML_PATH_VOUCHER_BAR_CODE);

        $discountItem = $this->dataObjectFactory->create();
        $discountPseudoProduct = $this->dataObjectFactory->create();
        $discountPseudoOrderItem = $this->dataObjectFactory->create();

        $voucherName = __('Voucher');
        if ($voucherBarCode) {
            $voucherName = sprintf("%s %s", $voucherBarCode, $voucherName);
        }

        $discountAmount = $entity->getDiscountAmount();
        if ($negativeAmount) {
            $discountAmount = $discountAmount * (-1);
        }
        $discountAmountWithoutTax = $discountAmount;
        if ($discountAmountWithoutTax < 0) {
            $discountAmountWithoutTax = $discountAmountWithoutTax * (-1);
        }
        $discountAmountWithoutTax = $discountAmountWithoutTax - $entity->getDiscountTaxCompensationAmount();
        $discountTaxCompensationAmount = $entity->getDiscountTaxCompensationAmount();

        $discountAmountWithoutTax = $discountAmountWithoutTax * (-1);
        $discountTaxCompensationAmount = $discountTaxCompensationAmount * (-1);

        $discountItem->setData('name', $voucherName)
            ->setData('sku', 'voucher')
            ->setData('qty', 1)
            ->setData(self::VAT_PERCENTAGE, self::DEFAULT_VAT_PERCENTAGE)
            ->setData('base_price', $discountAmountWithoutTax)
            ->setData('price', $discountAmountWithoutTax)
            ->setData('row_total', $discountAmountWithoutTax)
            ->setData('row_total_incl_tax', $discountAmount)
            ->setData('tax_amount', $discountTaxCompensationAmount);

        $discountPseudoOrderItem->setData('product', $discountPseudoProduct);
        $discountItem->setData('order_item', $discountPseudoOrderItem);

        return $discountItem;
    }

    public function getDiscountItem($entityItem, $negativeAmount = true)
    {
        if (!$entityItem->getDiscountAmount()) {
            return false;
        }
        $discountItem = $this->dataObjectFactory->create();
        $discountPseudoProduct = $this->dataObjectFactory->create();
        $discountPseudoOrderItem = $this->dataObjectFactory->create();

        $discountClassTaxId = $entityItem->getOrderItem()->getProduct()->getTaxClassId();

        if ($discountClassTaxId) {
            $discountPseudoProduct->setData('tax_class_id', $discountClassTaxId);
        }

        $discountAmount = $entityItem->getDiscountAmount();
        if ($negativeAmount) {
            $discountAmount = $discountAmount * (-1);
        }
        $discountAmountWithoutTax = $discountAmount;
        if ($discountAmountWithoutTax < 0) {
            $discountAmountWithoutTax = $discountAmountWithoutTax * (-1);
        }
        $discountAmountWithoutTax = $discountAmountWithoutTax - $entityItem->getDiscountTaxCompensationAmount();

        $discountItem->setData('name', __('Discount'))
            ->setData('sku', 'discount')
            ->setData('qty', 1)
            ->setData('base_price', $discountAmountWithoutTax)
            ->setData('price', $discountAmountWithoutTax)
            ->setData('row_total', $discountAmountWithoutTax)
            ->setData('row_total_incl_tax', $discountAmount)
            ->setData('tax_amount', $entityItem->getDiscountTaxCompensationAmount());

        $discountPseudoOrderItem->setData('product', $discountPseudoProduct);
        $discountItem->setData('order_item', $discountPseudoOrderItem);

        return $discountItem;
    }

    public function getEntityItems($entity, $order)
    {
        $customerId = $order->getCustomerId();

        $items = $entity->getItems();
        $entityItems = [];

        $negativeAmount = ($entity->getEntityType() == 'creditmemo') ? false : true;

        foreach ($items as $item) {
            if ($item->getOrderItem()->getParentItem()) {
                continue;
            }
            $entityItems[] = $item;
        }

        $discountItem = $this->getDiscountEntityItem($entity, $negativeAmount);
        if ($discountItem) {
            $entityItems[] = $discountItem;
        }

        $shippingItem = $this->getShippingItem($entity, $order);
        if ($shippingItem) {
            $entityItems[] = $shippingItem;
        }

        return $entityItems;
    }

    public function getTaxRate($entityItem, $customerId, $storeId)
    {
        $taxRate = 0;
        if ($entityItem->getData(self::VAT_PERCENTAGE)) {
            $taxRate = $entityItem->getData(self::VAT_PERCENTAGE);
        } elseif ($entityItem->getOrderItem()->getTaxPercent()) {
            $taxRate = round($entityItem->getOrderItem()->getTaxPercent(), 0);
        } else {
            $productTaxClassId = $entityItem->getOrderItem()->getProduct()->getTaxClassId();
            $taxRate = $this->taxCalculationApi->getCalculatedRate($productTaxClassId, $customerId, $storeId);
        }

        return $taxRate;
    }

    public function getConfigValue($path, $storeId = null)
    {
        return $this->helper->getConfigValue($path, $storeId);
    }

    public function getStoreRegion()
    {
        $regionId = $this->getConfigValue(\Hawksama\Invoice\Helper\Data::XML_PATH_GENERAL_STORE_INFORMATION_REGION_ID);
        if (!$regionId) {
            return false;
        }

        $regionModel = $this->regionFactory->create()->load($regionId);
        if (!$regionModel->getId()) {
            return false;
        }
        return $regionModel->getName();
    }

    public function getFormattedDate($format, $date)
    {
        return $this->dateTime->date($format, $date);
    }

    public function getFontPath()
    {
        return sprintf("%s/%s", $this->fileSystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath('fonts/Arial'), "ArialBold.ttf");
    }

    public function getCurrentCurrencySymbol()
    {
        return $this->priceCurrency->getCurrency()->getCode();
    }

    public function getBreakpoints($items)
    {
        $breakpoints = [];
        $rowLength = 40;

        $itemCounter = 0;
        $rowsCounter = 0;
        $breakpoints[] = 0;

        foreach ($items as $item) {
            $product = $item->getOrderItem()->getProduct();
            $productBarCode = false;
            if ($product && $product->getCodBare()) {
                $productBarCode = $product->getCodBare();
            } else if ($item->getCodBare()) {
                $productBarCode = $item->getCodBare();
            }
            $productName = $item->getName();
            if ($productBarCode) {
                $productName = sprintf("%s %s", $productBarCode, $productName);
            }

            $productNameLength = strlen($productName);
            $splittedProductName = explode(" ", $productName);

            $productNameProcessed = $productNameLength / $rowLength;
            $nextRows = 0;
            if ($productNameProcessed > 1) {
                $nextRows = ceil($productNameProcessed);
            } else {
                $nextRows = 1;
            }
            foreach ($splittedProductName as $eachNamePart) {
                $eachNamePartLength = strlen($eachNamePart);
                if ($eachNamePartLength > $rowLength / 2) {
                    $nextRows++;
                }
            }
            $rowsCounter += $nextRows;

            $currentRow = $rowsCounter / self::MAXIMUM_PRODUCTS_ON_PAGE;

            if ($currentRow > 1) {
                $breakpoints[] = $itemCounter;
                $rowsCounter = 0;
            }
            $itemCounter++;
        }

        return $breakpoints;
    }
}
