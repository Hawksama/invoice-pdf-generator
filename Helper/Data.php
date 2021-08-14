<?php

namespace Hawksama\Invoice\Helper;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory as ConfigCollectionFactory;
use Magento\Catalog\Helper\Data as CatalogHelper;
use Magento\Theme\Block\Html\Header\Logo;

/**
 * Data class
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_SALES_PDF_INVOICE_DOMPDF_ENABLED = 'invoice_pdf/invoice/dompdf_enabled';
    const XML_PATH_SALES_PDF_INVOICE_DOMPDF_LEFT_NOTE_BLOCK = 'invoice_pdf/invoice/left_note_block';
    const XML_PATH_SALES_PDF_INVOICE_DOMPDF_RIGHT_NOTE_BLOCK = 'invoice_pdf/invoice/right_note_block';

    const XML_PATH_SALES_PDF_CREDITMEMO_DOMPDF_ENABLED = 'invoice_pdf/creditmemo/dompdf_enabled';
    const XML_PATH_SALES_PDF_CREDITMEMO_DOMPDF_LEFT_NOTE_BLOCK = 'invoice_pdf/creditmemo/left_note_block';
    const XML_PATH_SALES_PDF_CREDITMEMO_DOMPDF_RIGHT_NOTE_BLOCK = 'invoice_pdf/creditmemo/right_note_block';

    // const XML_PATH_SALES_PDF_PACKING_ORDER_STATUS = 'invoice_pdf/packing_pdf/order_status';

    const XML_PATH_GENERAL_STORE_INFORMATION_PROVIDER = 'general/store_information/provider';
    const XML_PATH_GENERAL_STORE_INFORMATION_TAX_DISPLAYED = 'general/store_information/default_tax_displayed';
    const XML_PATH_GENERAL_STORE_INFORMATION_NR_REG_COM = 'general/store_information/nr_reg_com';
    const XML_PATH_GENERAL_STORE_INFORMATION_CUI = 'general/store_information/cui';
    const XML_PATH_GENERAL_STORE_INFORMATION_FISCAL_ATTRIBUTE = 'general/store_information/fiscal_attribute';
    const XML_PATH_GENERAL_STORE_INFORMATION_SOCIAL_CAPITAL = 'general/store_information/social_capital';
    const XML_PATH_GENERAL_STORE_INFORMATION_BANK_ACCOUNT = 'general/store_information/bank_account';
    const XML_PATH_GENERAL_STORE_INFORMATION_BANK_NAME = 'general/store_information/bank_name';

    const XML_PATH_GENERAL_STORE_INFORMATION_CITY = 'general/store_information/city';
    const XML_PATH_GENERAL_STORE_INFORMATION_REGION_ID = 'general/store_information/region_id';
    const XML_PATH_GENERAL_STORE_INFORMATION_STREET = 'general/store_information/street_line1';

    const MEDIA_Hawksama_SALES = 'hawksama_invoice';

    const XML_PATH_GIFT_WRAP_NAME = 'amasty_checkout/gifts/gift_wrap_name';
    const XML_PATH_GIFT_WRAP_BAR_CODE = 'amasty_checkout/gifts/gift_wrap_barcode';
    const XML_PATH_GIFT_WRAP_IMAGE = 'amasty_checkout/gifts/gift_wrap_image';
    const XML_PATH_GIFT_WRAP_TAX_CLASS = 'amasty_checkout/gifts/gift_tax_class';
    const XML_PATH_GIFT_WRAP_PRODUCT_ID = 'amasty_checkout/gifts/gift_wrap_product_id';

    const XML_PATH_SHIPPING_BAR_CODE = 'shipping/invoice/shipping_barcode';

    const XML_PATH_VOUCHER_BAR_CODE = 'invoice_pdf/voucher/barcode';

    const XML_PATH_SALES_INVOICE_EXPORT_LAST_DATE_EROTIC = 'hawksama_invoice/invoice_export/last_date_erotic';
    const XML_PATH_SALES_INVOICE_EXPORT_LAST_DATE_BAGSY = 'hawksama_invoice/invoice_export/last_date_bagsy';
    const XML_PATH_SALES_INVOICE_EXPORT_LAST_DATE_CARNAVAL = 'hawksama_invoice/invoice_export/last_date_carnaval';
    const XML_PATH_SALES_INVOICE_EXPORT_LAST_DATE = 'hawksama_invoice/invoice_export/last_date';
    const XML_PATH_SALES_INVOICE_EXPORT_SECTION = 'hawksama_invoice/invoice_export';

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var WriterInterface
     */
    protected $configWriter;

    /**
     * @var ConfigCollectionFactory
     */
    protected $configCollectionFactory;

    /**
     * @var CatalogHelper
     */
    protected $catalogHelper;

    /**
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter,
        ConfigCollectionFactory $configCollectionFactory,
        CatalogHelper $catalogHelper,
        Logo $logo
    ) {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
        $this->configCollectionFactory = $configCollectionFactory;
        $this->catalogHelper = $catalogHelper;
        $this->logo = $logo;
    }

    /**
     * @param string $value
     * @return string
     */
    public function getConfigValue($value, $store = null)
    {
        if (!$store) {
            $store = $this->storeManager->getStore();
        }
        return $this->scopeConfig->getValue(
            $value,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function getImage ($configPath)
    {
        if ($filePath = $this->getConfigValue($configPath)) {
            return sprintf('%s' . self::MEDIA_Hawksama_SALES . '/%s', $this->getMediaUrl(), $filePath);
        }
        return false;
    }

    public function getImagePath ($configPath)
    {
        if ($filePath = $this->getConfigValue($configPath)) {
            return sprintf("%s/%s", self::MEDIA_Hawksama_SALES, $filePath);
        }
        return false;
    }

    public function getMediaUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }

    public function getLastInvoiceExportDate($section, $ignoreCache = true)
    {
        if ($ignoreCache) {
            $configValues = $this->configCollectionFactory->create()->addScopeFilter(
                \Magento\Framework\App\ScopeInterface::SCOPE_DEFAULT,
                0,
                self::XML_PATH_SALES_INVOICE_EXPORT_SECTION
            );
            foreach ($configValues as $configValue) {
                if ($configValue->getPath() == $section) {
                    return $configValue->getValue();
                }
            }
        }
        return $this->getConfigValue($section);
    }

    /**
     * Save last sync arrival id
     *
     * @param string $dateTime
     * @return void
     */
    public function setLastInvoiceExportDate($section, $date)
    {
        $this->configWriter->save($section, $date);
    }

    public function getGiftWrapTaxPrice($price, $includingTax = null, $shippingAddress = null, $ctc = null, $store = null)
    {
        $pseudoProduct = new \Magento\Framework\DataObject();
        $pseudoProduct->setTaxClassId($this->getConfigValue(self::XML_PATH_GIFT_WRAP_TAX_CLASS, $store));

        $billingAddress = false;
        if ($shippingAddress && $shippingAddress->getQuote() && $shippingAddress->getQuote()->getBillingAddress()) {
            $billingAddress = $shippingAddress->getQuote()->getBillingAddress();
        }

        $price = $this->catalogHelper->getTaxPrice(
            $pseudoProduct,
            $price,
            $includingTax,
            $shippingAddress,
            $billingAddress,
            $ctc,
            $store,
            true
        );

        return $price;
    }

    public function getGiftWrapProductId()
    {
        return $this->getConfigValue(self::XML_PATH_GIFT_WRAP_PRODUCT_ID);
    }

    public function getLogo()
    {
        $img  = '<img src="' . $this->logo->getLogoSrc() . '"';
        $img .= 'alt="' . $this->logo->getLogogetLogoAlt() . '"';
        $img .= $this->logo->getLogoWidth() ? 'width="' . $this->logo->getLogoWidth() . '"' : '';
        $img .= $this->logo->getLogoHeight() ? 'height="' . $this->logo->getLogoHeight() . '"' : '';
        $img .= '/>';

        return $img;
    }

    public function stripAccents($string)
    {
        if (!preg_match('/[\x80-\xff]/', $string)) {
            return $string;
        }

        if (self::seems_utf8($string)) {
            $chars = [
                // Decompositions for Latin-1 Supplement
                chr(195) . chr(128) => 'A', chr(195) . chr(129) => 'A',
                chr(195) . chr(130) => 'A', chr(195) . chr(131) => 'A',
                chr(195) . chr(132) => 'A', chr(195) . chr(133) => 'A',
                chr(195) . chr(135) => 'C', chr(195) . chr(136) => 'E',
                chr(195) . chr(137) => 'E', chr(195) . chr(138) => 'E',
                chr(195) . chr(139) => 'E', chr(195) . chr(140) => 'I',
                chr(195) . chr(141) => 'I', chr(195) . chr(142) => 'I',
                chr(195) . chr(143) => 'I', chr(195) . chr(145) => 'N',
                chr(195) . chr(146) => 'O', chr(195) . chr(147) => 'O',
                chr(195) . chr(148) => 'O', chr(195) . chr(149) => 'O',
                chr(195) . chr(150) => 'O', chr(195) . chr(153) => 'U',
                chr(195) . chr(154) => 'U', chr(195) . chr(155) => 'U',
                chr(195) . chr(156) => 'U', chr(195) . chr(157) => 'Y',
                chr(195) . chr(159) => 's', chr(195) . chr(160) => 'a',
                chr(195) . chr(161) => 'a', chr(195) . chr(162) => 'a',
                chr(195) . chr(163) => 'a', chr(195) . chr(164) => 'a',
                chr(195) . chr(165) => 'a', chr(195) . chr(167) => 'c',
                chr(195) . chr(168) => 'e', chr(195) . chr(169) => 'e',
                chr(195) . chr(170) => 'e', chr(195) . chr(171) => 'e',
                chr(195) . chr(172) => 'i', chr(195) . chr(173) => 'i',
                chr(195) . chr(174) => 'i', chr(195) . chr(175) => 'i',
                chr(195) . chr(177) => 'n', chr(195) . chr(178) => 'o',
                chr(195) . chr(179) => 'o', chr(195) . chr(180) => 'o',
                chr(195) . chr(181) => 'o', chr(195) . chr(182) => 'o',
                chr(195) . chr(182) => 'o', chr(195) . chr(185) => 'u',
                chr(195) . chr(186) => 'u', chr(195) . chr(187) => 'u',
                chr(195) . chr(188) => 'u', chr(195) . chr(189) => 'y',
                chr(195) . chr(191) => 'y',
                // Decompositions for Latin Extended-A
                chr(196) . chr(128) => 'A', chr(196) . chr(129) => 'a',
                chr(196) . chr(130) => 'A', chr(196) . chr(131) => 'a',
                chr(196) . chr(132) => 'A', chr(196) . chr(133) => 'a',
                chr(196) . chr(134) => 'C', chr(196) . chr(135) => 'c',
                chr(196) . chr(136) => 'C', chr(196) . chr(137) => 'c',
                chr(196) . chr(138) => 'C', chr(196) . chr(139) => 'c',
                chr(196) . chr(140) => 'C', chr(196) . chr(141) => 'c',
                chr(196) . chr(142) => 'D', chr(196) . chr(143) => 'd',
                chr(196) . chr(144) => 'D', chr(196) . chr(145) => 'd',
                chr(196) . chr(146) => 'E', chr(196) . chr(147) => 'e',
                chr(196) . chr(148) => 'E', chr(196) . chr(149) => 'e',
                chr(196) . chr(150) => 'E', chr(196) . chr(151) => 'e',
                chr(196) . chr(152) => 'E', chr(196) . chr(153) => 'e',
                chr(196) . chr(154) => 'E', chr(196) . chr(155) => 'e',
                chr(196) . chr(156) => 'G', chr(196) . chr(157) => 'g',
                chr(196) . chr(158) => 'G', chr(196) . chr(159) => 'g',
                chr(196) . chr(160) => 'G', chr(196) . chr(161) => 'g',
                chr(196) . chr(162) => 'G', chr(196) . chr(163) => 'g',
                chr(196) . chr(164) => 'H', chr(196) . chr(165) => 'h',
                chr(196) . chr(166) => 'H', chr(196) . chr(167) => 'h',
                chr(196) . chr(168) => 'I', chr(196) . chr(169) => 'i',
                chr(196) . chr(170) => 'I', chr(196) . chr(171) => 'i',
                chr(196) . chr(172) => 'I', chr(196) . chr(173) => 'i',
                chr(196) . chr(174) => 'I', chr(196) . chr(175) => 'i',
                chr(196) . chr(176) => 'I', chr(196) . chr(177) => 'i',
                chr(196) . chr(178) => 'IJ',chr(196) . chr(179) => 'ij',
                chr(196) . chr(180) => 'J', chr(196) . chr(181) => 'j',
                chr(196) . chr(182) => 'K', chr(196) . chr(183) => 'k',
                chr(196) . chr(184) => 'k', chr(196) . chr(185) => 'L',
                chr(196) . chr(186) => 'l', chr(196) . chr(187) => 'L',
                chr(196) . chr(188) => 'l', chr(196) . chr(189) => 'L',
                chr(196) . chr(190) => 'l', chr(196) . chr(191) => 'L',
                chr(197) . chr(128) => 'l', chr(197) . chr(129) => 'L',
                chr(197) . chr(130) => 'l', chr(197) . chr(131) => 'N',
                chr(197) . chr(132) => 'n', chr(197) . chr(133) => 'N',
                chr(197) . chr(134) => 'n', chr(197) . chr(135) => 'N',
                chr(197) . chr(136) => 'n', chr(197) . chr(137) => 'N',
                chr(197) . chr(138) => 'n', chr(197) . chr(139) => 'N',
                chr(197) . chr(140) => 'O', chr(197) . chr(141) => 'o',
                chr(197) . chr(142) => 'O', chr(197) . chr(143) => 'o',
                chr(197) . chr(144) => 'O', chr(197) . chr(145) => 'o',
                chr(197) . chr(146) => 'OE',chr(197) . chr(147) => 'oe',
                chr(197) . chr(148) => 'R',chr(197) . chr(149) => 'r',
                chr(197) . chr(150) => 'R',chr(197) . chr(151) => 'r',
                chr(197) . chr(152) => 'R',chr(197) . chr(153) => 'r',
                chr(197) . chr(154) => 'S',chr(197) . chr(155) => 's',
                chr(197) . chr(156) => 'S',chr(197) . chr(157) => 's',
                chr(197) . chr(158) => 'S',chr(197) . chr(159) => 's',
                chr(197) . chr(160) => 'S', chr(197) . chr(161) => 's',
                chr(197) . chr(162) => 'T', chr(197) . chr(163) => 't',
                chr(197) . chr(164) => 'T', chr(197) . chr(165) => 't',
                chr(197) . chr(166) => 'T', chr(197) . chr(167) => 't',
                chr(197) . chr(168) => 'U', chr(197) . chr(169) => 'u',
                chr(197) . chr(170) => 'U', chr(197) . chr(171) => 'u',
                chr(197) . chr(172) => 'U', chr(197) . chr(173) => 'u',
                chr(197) . chr(174) => 'U', chr(197) . chr(175) => 'u',
                chr(197) . chr(176) => 'U', chr(197) . chr(177) => 'u',
                chr(197) . chr(178) => 'U', chr(197) . chr(179) => 'u',
                chr(197) . chr(180) => 'W', chr(197) . chr(181) => 'w',
                chr(197) . chr(182) => 'Y', chr(197) . chr(183) => 'y',
                chr(197) . chr(184) => 'Y', chr(197) . chr(185) => 'Z',
                chr(197) . chr(186) => 'z', chr(197) . chr(187) => 'Z',
                chr(197) . chr(188) => 'z', chr(197) . chr(189) => 'Z',
                chr(197) . chr(190) => 'z', chr(197) . chr(191) => 's',chr(537)=>'s',
                // Euro Sign
                chr(226) . chr(130) . chr(172) => 'E',
                // GBP (Pound) Sign
                chr(194) . chr(163) => '',
                // Romanian CHARS
                "\xC4\x82" => "A",
                "\xC4\x83" => "a",
                "\xC3\x82" => "A", 
                "\xC3\xA2" => "a", 
                "\xC3\x8E" => "I", 
                "\xC3\xAE" => "i", 
                "\xC8\x98" => "S", 
                "\xC8\x99" => "s", 
                "\xC8\x9A" => "T", 
                "\xC8\x9B" => "t", 
                "\xC5\x9E" => "S", 
                "\xC5\x9F" => "s", 
                "\xC5\xA2" => "T", 
                "\xC5\xA3" => "t"
            ];

            $string = strtr($string, $chars);
        } else {
            // Assume ISO-8859-1 if not UTF-8
            $chars['in'] = chr(128) . chr(131) . chr(138) . chr(142) . chr(154) . chr(158)
                . chr(159) . chr(162) . chr(165) . chr(181) . chr(192) . chr(193) . chr(194)
                . chr(195) . chr(196) . chr(197) . chr(199) . chr(200) . chr(201) . chr(202)
                . chr(203) . chr(204) . chr(205) . chr(206) . chr(207) . chr(209) . chr(210)
                . chr(211) . chr(212) . chr(213) . chr(214) . chr(216) . chr(217) . chr(218)
                . chr(219) . chr(220) . chr(221) . chr(224) . chr(225) . chr(226) . chr(227)
                . chr(228) . chr(229) . chr(231) . chr(232) . chr(233) . chr(234) . chr(235)
                . chr(236) . chr(237) . chr(238) . chr(239) . chr(241) . chr(242) . chr(243)
                . chr(244) . chr(245) . chr(246) . chr(248) . chr(249) . chr(250) . chr(251)
                . chr(252) . chr(253) . chr(255);

            $chars['out'] = "EfSZszYcYuAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy";

            $string = strtr($string, $chars['in'], $chars['out']);
            $double_chars['in'] = [chr(140), chr(156), chr(198), chr(208), chr(222), chr(223), chr(230), chr(240), chr(254)];
            $double_chars['out'] = ['OE', 'oe', 'AE', 'DH', 'TH', 'ss', 'ae', 'dh', 'th'];
            $string = str_replace($double_chars['in'], $double_chars['out'], $string);
        }

        return $string;
    }

    public static function seems_utf8($str)
    {
        $length = strlen($str);
        for ($i=0; $i < $length; $i++) {
            $c = ord($str[$i]);
            if ($c < 0x80) {
                $n = 0;
            } # 0bbbbbbb
            elseif (($c & 0xE0) == 0xC0) {
                $n=1;
            } # 110bbbbb
            elseif (($c & 0xF0) == 0xE0) {
                $n=2;
            } # 1110bbbb
            elseif (($c & 0xF8) == 0xF0) {
                $n=3;
            } # 11110bbb
            elseif (($c & 0xFC) == 0xF8) {
                $n=4;
            } # 111110bb
            elseif (($c & 0xFE) == 0xFC) {
                $n=5;
            } # 1111110b
            else {
                return false;
            } # Does not match any model
            for ($j=0; $j<$n; $j++) { # n bytes matching 10bbbbbb follow ?
                if ((++$i == $length) || ((ord($str[$i]) & 0xC0) != 0x80)) {
                    return false;
                }
            }
        }
        return true;
    }
}
