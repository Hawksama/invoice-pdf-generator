<?php

namespace Hawksama\Invoice\Model\Config\Backend;

/**
 * Image class
 */
class Image extends \Magento\Config\Model\Config\Backend\Image
{
    protected function _getUploadDir()
    {
        return $this->_mediaDirectory->getAbsolutePath(
            $this->_appendScopeInfo(\Hawksama\Invoice\Helper\Data::MEDIA_Hawksama_SALES)
        );
    }

    protected function _addWhetherScopeInfo()
    {
        return true;
    }

    protected function _getAllowedExtensions()
    {
        return ['jpg', 'jpeg', 'gif', 'png', 'svg'];
    }
}
