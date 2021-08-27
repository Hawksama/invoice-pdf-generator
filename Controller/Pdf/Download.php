<?php

namespace Hawksama\Invoice\Controller\Pdf;

use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action;
use Magento\Sales\Model\Order\Invoice;
use Exception;
use Psr\Log\LoggerInterface;
use Dompdf\Dompdf;
use Magento\Framework\View\LayoutFactory;
use Magento\Framework\Controller\Result\RedirectFactory;

class Download extends Action\Action
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var Invoice
     */
    protected $invoice;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Dompdf
     */
    protected $dompdf;

    /**
     * @var LayoutFactory
     */
    protected $layoutFactory;

    /**
     * @var RedirectFactory
     */
    protected $redirect;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param Context $context
     * @param Session $customerSession
     * @param Invoice $invoice
     * @param LoggerInterface $logger
     * @param Dompdf $dompdf
     * @param LayoutFactory $layoutFactory
     * @param RedirectFactory $redirect
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        Invoice $invoice,
        LoggerInterface $logger,
        Dompdf $dompdf,
        LayoutFactory $layoutFactory,
        RedirectFactory $redirect
    ) {
        $this->customerSession = $customerSession;
        $this->invoice = $invoice;
        $this->logger = $logger;
        $this->dompdf = $dompdf;
        $this->layoutFactory = $layoutFactory;
        $this->redirect = $redirect;

        parent::__construct($context);
    }

    public function execute()
    {
        $invoiceId = (int)$this->getRequest()->getParam('id');

        // if invoice id parameter is not sent, create redirect
        if (!$invoiceId) {
            $resultRedirect = $this->redirect->create();
            $resultRedirect->setPath('sales/order/history');
            return $resultRedirect;
        }

        try {
            $invoiceData = $this->invoice->load($invoiceId);
        } catch (Exception $exception) {
            $this->logger->critical($exception->getMessage());
            $invoiceData = null;
        }

        // if invoice is empty, create redirect
        if (!$invoiceData || !$invoiceData->getId()) {
            $resultRedirect = $this->redirect->create();
            $resultRedirect->setPath('sales/order/history');
            return $resultRedirect;
        }

        $order = $invoiceData->getOrder();
        $customerId = $this->customerSession->getCustomerId();

        // if invoice is own by the current user => download the pdf, else create redirect
        if (
            $order->getId()
            && $order->getCustomerId()
            && $order->getCustomerId() == $customerId
        ) {
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

            $currentBlock = $layout->createBlock('Hawksama\Invoice\Block\Adminhtml\Invoice\Pdf')
                ->setData('invoice', $invoiceData);
            $currentBlock->setTemplate('Hawksama_Invoice::invoice/template.phtml');
            $invoiceIncrementId = $invoiceData->getIncrementId();
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
                    'items_count' => count($currentBlock->getEntityItems($invoiceData, $invoiceData->getOrder()))
                ];
            }

            $invoicesContent .= $currentBlockHtml;

            $pdfName[] = $invoiceData->getIncrementId();

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
        } else {
            $resultRedirect = $this->redirect->create();
            $resultRedirect->setPath('customer/account/login');
            return $resultRedirect;
        }
    }
}
