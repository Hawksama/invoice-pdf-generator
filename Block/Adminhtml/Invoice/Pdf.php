<?php

namespace Hawksama\Invoice\Block\Adminhtml\Invoice;

/**
 * Pdf class
 */
class Pdf extends \Hawksama\Invoice\Block\Adminhtml\AbstractPdf
{
    public function getInvoice()
    {
        return $this->getData('invoice');
    }

    public function getLogo()
    {
        return $this->helper->getLogo();
    }

    public function stripAccents($string)
    {
        if (is_object($string)) {
            if ($string->getText()) {
                return $this->helper->stripAccents($string->getText());
            }
        }
        return $this->helper->stripAccents($string);
    }
}
