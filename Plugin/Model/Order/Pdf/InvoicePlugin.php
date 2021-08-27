<?php

namespace Hawksama\Invoice\Plugin\Model\Order\Pdf;

use Hawksama\Invoice\Helper\Data;
use Dompdf\Dompdf;
use Magento\Framework\View\LayoutFactory;

/**
 * InvoicePlugin class
 */
class InvoicePlugin
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
    ) {
        $this->helper = $helper;
        $this->dompdf = $dompdf;
        $this->layoutFactory = $layoutFactory;
    }

    /**
     * @param \Magento\Sales\Model\Order\Pdf\Invoice $subject
     * @param callable $proceed
     * @param array $invoices
     * @return void
     */
    public function aroundGetPdf(\Magento\Sales\Model\Order\Pdf\Invoice $subject, callable $proceed, $invoices = [])
    {
        if (!$this->helper->getConfigValue(\Hawksama\Invoice\Helper\Data::XML_PATH_SALES_PDF_INVOICE_DOMPDF_ENABLED)) {
            return $proceed($invoices);
        }

        $dompdf = $this->dompdf;
        $layout = $this->layoutFactory->create();

        $invoicesContent = '<html>
            <head>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8;" />
            </head>
            <body>
        ';

        $pdfName = [];
        $invoicePrefix = "";
        $pdfInvoicePrefixes = [];

        $lastPage = 0;

        foreach ($invoices as $invoice) {
            $currentBlock = $layout->createBlock('Hawksama\Invoice\Block\Adminhtml\Invoice\Pdf')
                ->setData('invoice', $invoice)
                ->setTemplate('Hawksama_Invoice::invoice/template.phtml');
            $invoiceIncrementId = $invoice->getIncrementId();
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
                $pdfInvoicePrefixes[] = [
                    'last_page' => $lastPage,
                    'pages' => $pages,
                    'prefix' => '',
                    'items_count' => count($currentBlock->getEntityItems($invoice, $invoice->getOrder()))
                ];
            }

            $invoicesContent .= $currentBlockHtml;

            $pdfName[] = $invoice->getIncrementId();
        }

        $invoicesContent .= "</body>
            </html>";

        // echo $invoicesContent;
        // die;

        $dompdf->set_option("isPhpEnabled", true);
        $dompdf->set_option('isRemoteEnabled', true);
        $dompdf->loadHtml($invoicesContent, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $font = $dompdf->getFontMetrics()->get_font("helvetica", "bold");

        $scriptString = '';
        $prefixCount = count($pdfInvoicePrefixes);
        if ($prefixCount) {
            if ($prefixCount > 1) {
                for ($prefixId = 0; $prefixId < count($pdfInvoicePrefixes); $prefixId++) {
                    $firstPage = 1;

                    if ($prefixId == 0) {
                        $scriptString .= 'if ($PAGE_NUM <= ' . $pdfInvoicePrefixes[$prefixId]['last_page'] . ') {';
                        $firstPage = 1;
                    } else {
                        $scriptString .= 'if ($PAGE_NUM > ' . $pdfInvoicePrefixes[$prefixId - 1]['last_page'] . ' && $PAGE_NUM <= ' . $pdfInvoicePrefixes[$prefixId]['last_page'] . ') {';
                        $firstPage = $pdfInvoicePrefixes[$prefixId - 1]['last_page'] + 1;
                    }

                    $scriptString .= '$pdf->text(405, 35, "' . $pdfInvoicePrefixes[$prefixId]['prefix'] . '", "helvetica", 13, array(0,0,0));';
                    $scriptString .= '}';
                    $scriptString .= 'if ($PAGE_NUM == ' . $firstPage . ') { $pdf->text(35, 35, "' . __('Numar total pagini') . ' ' . $pdfInvoicePrefixes[$prefixId]['pages'] . ' • ' . __('Total produse') . ' ' . $pdfInvoicePrefixes[$prefixId]['items_count'] . '", "helvetica", 10, array(0,0,0)); }';
                }
            } else {
                $prefixArray = end($pdfInvoicePrefixes);
                $scriptString = '$pdf->text(405, 35, "' . $prefixArray['prefix'] . '", "helvetica", 13, array(0,0,0));';
                $scriptString .= 'if ($PAGE_NUM == 1) { $pdf->text(35, 35, "' . 'Numar total pagini ' . $prefixArray['pages'] . ' • ' . __('Total produse') . ' ' . $prefixArray['items_count'] . '", "helvetica", 10, array(0,0,0)); }';
            }

            $dompdf->getCanvas()->page_script($scriptString);
        }

        $dompdf->stream(implode("_", $pdfName));

        return $dompdf;
    }
}
