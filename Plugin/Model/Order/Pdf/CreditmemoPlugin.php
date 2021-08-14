<?php

namespace Hawksama\Invoice\Plugin\Model\Order\Pdf;

use Hawksama\Invoice\Helper\Data;
use Dompdf\Dompdf;
use Magento\Framework\View\LayoutFactory;

/**
 * CreditmemoPlugin class
 */
class CreditmemoPlugin
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Dompdf
     */
    private $dompdf;

    public function __construct(
        Data $helper,
        Dompdf $dompdf,
        LayoutFactory $layoutFactory
    ){
        $this->helper = $helper;
        $this->dompdf = $dompdf;
        $this->layoutFactory = $layoutFactory;
    }

    /**
     * @param \Magento\Sales\Model\Order\Pdf\Creditmemo $subject
     * @param callable $proceed
     * @param array $creditmemos
     * @return void
     */
    public function aroundGetPdf(\Magento\Sales\Model\Order\Pdf\Creditmemo $subject, callable $proceed, $creditmemos = [])
    {
        if (!$this->helper->getConfigValue(\Hawksama\Invoice\Helper\Data::XML_PATH_SALES_PDF_CREDITMEMO_DOMPDF_ENABLED)) {
            return $proceed($creditmemos);
        }

        $dompdf = $this->dompdf;
        $layout = $this->layoutFactory->create();

        $creditMemosContent = '<html>
            <head>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
            </head>
            <body>
        ';

        $pdfName = [];
        $creditmemoPrefix = "";
        $lastPage = 0;
        $pdfCreditmemoPrefixes = [];

        foreach ($creditmemos as $creditmemo) {
            $currentBlock = $layout->createBlock('Hawksama\Invoice\Block\Adminhtml\Creditmemo\Pdf')
                ->setData('creditmemo', $creditmemo)
                ->setTemplate('Hawksama_Invoice::creditmemo/template.phtml');

            $creditmemoIncrementId = $creditmemo->getIncrementId();
            $currentBlockHtml = $currentBlock->toHtml();

            $documentObject = new \DOMDocument();
            $internalErrors = libxml_use_internal_errors(true);
            $documentObject->loadHTML($currentBlockHtml);

            libxml_use_internal_errors($internalErrors);
            $documentSelector = new \DOMXPath($documentObject);

            $result = $documentSelector->query('//span[contains(@id,"number-of-pages")]');

            if ($result->length) {
                $pages = $result->item(0)->nodeValue;
                $lastPage += $pages;
                $pdfCreditmemoPrefixes[] = [
                    'last_page' => $lastPage,
                    'pages' => $pages,
                    'prefix' => '',
                    'items_count' => count($currentBlock->getEntityItems($creditmemo, $creditmemo->getOrder()))
                ];
            }

            $creditMemosContent .= $currentBlockHtml;

            $pdfName[] = $creditmemo->getIncrementId();
        }

        $creditMemosContent .= "</body>
            </html>";

        // echo $creditMemosContent;
        // die;

        $dompdf->set_option("isPhpEnabled", true);
        $dompdf->set_option('isRemoteEnabled', true);
        $dompdf->loadHtml($creditMemosContent, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $font = $dompdf->getFontMetrics()->get_font("helvetica", "bold");

        $scriptString = '';
        $prefixCount = count($pdfCreditmemoPrefixes);
        if ($prefixCount) {
            if ($prefixCount > 1) {
                for ($prefixId = 0; $prefixId < count($pdfCreditmemoPrefixes); $prefixId++) {
                    $firstPage = 1;
                    if ($prefixId == 0) {
                        $scriptString .= 'if ($PAGE_NUM <= ' . $pdfCreditmemoPrefixes[$prefixId]['last_page'] . ') {';
                        $firstPage = 1;
                    } else {
                        $scriptString .= 'if ($PAGE_NUM > ' . $pdfCreditmemoPrefixes[$prefixId - 1]['last_page'] . ' && $PAGE_NUM <= ' . $pdfCreditmemoPrefixes[$prefixId]['last_page'] . ') {';
                        $firstPage = $pdfCreditmemoPrefixes[$prefixId - 1]['last_page'] + 1;
                    }
                    $scriptString .= '$pdf->text(380, 35, "' . $pdfCreditmemoPrefixes[$prefixId]['prefix'] . '", "helvetica", 13, array(0,0,0));';
                    $scriptString .= '}';
                    $scriptString .= 'if ($PAGE_NUM == ' . $firstPage . ') { $pdf->text(35, 35, "' . 'Numar total pagini ' . $pdfCreditmemoPrefixes[$prefixId]['pages'] . ' • ' . __('Total produse') . ' ' . $pdfCreditmemoPrefixes[$prefixId]['items_count'] . '", "helvetica", 10, array(0,0,0)); }';
                }
            } else {
                $prefixArray = end($pdfCreditmemoPrefixes);
                $scriptString = '$pdf->text(380, 35, "' . $prefixArray['prefix'] . '", "helvetica", 13, array(0,0,0));';
                $scriptString .= 'if ($PAGE_NUM == 1) { $pdf->text(35, 35, "' . 'Numar total pagini ' . $prefixArray['pages'] . ' • ' . __('Total produse') . ' ' . $prefixArray['items_count'] . '", "helvetica", 10, array(0,0,0)); }';
            }
            $dompdf->getCanvas()->page_script($scriptString);
        }

        $dompdf->stream(implode("_", $pdfName));

        return $dompdf;
    }
}
