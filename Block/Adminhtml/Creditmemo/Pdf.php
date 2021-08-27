<?php

namespace Hawksama\Invoice\Block\Adminhtml\Creditmemo;

/**
 * Pdf class
 */
class Pdf extends \Hawksama\Invoice\Block\Adminhtml\AbstractPdf
{
    public function getCreditmemo()
    {
        return $this->getData('creditmemo');
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
