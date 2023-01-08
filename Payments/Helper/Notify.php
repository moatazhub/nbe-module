<?php

namespace Ahly\Payments\Helper;

use Magento\Framework\Event\ManagerInterface as EventManager;

class Notify
{
	/**
	 * @var EventManager
	 */
	private $eventManager;

	public function __construct(
		\Magento\Sales\Model\Order $order,
		\Magento\Sales\Model\Service\InvoiceService $invoice,
		\Magento\Framework\Controller\ResultFactory $resultFactory,
		\Magento\Framework\App\ResourceConnection $resource,
		\Magento\Customer\Model\Customer $customer,
		$messageManager,
		$base_url,
		$website_id,
		EventManager $eventManager,
		$logger

	) {
		$this->order = $order;
		$this->invoice = $invoice;
		$this->resultFactory = $resultFactory;
		$this->resource = $resource;
		$this->customer = $customer;
		$this->messageManager = $messageManager;
		$this->base_url = $base_url;
		$this->website_id = $website_id;
		$this->eventManager = $eventManager;
		$this->logger = $logger;
	}



	/**
	 * Shared function all incoming notifications pass through here.
	 * Usage: ping → pong
	 */
	public function Pong($order_id, $resultIndicator, $isCanceled, $isTimeout)
	{

		try {

			if ($_SERVER['REQUEST_METHOD'] === "GET") {

				$response = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
				$order = $this->order->loadByIncrementId($order_id);
               // $this->logger->info("isCanceled: " . $isCanceled);
				if($isCanceled === 'true'){
					$this->logger->info("Payment failed : your order was canceled.");
					$order->addStatusHistoryComment(__("Payment failed : your order was canceled."))
						->save();
					$response_url = $this->base_url . 'checkout/onepage/failure';
					$this->messageManager->addError(__("Payment failed : your order was canceled."));
					$response->setUrl($response_url);
					return $response;
				}

				if($isTimeout === 'true'){
					$this->logger->info("Payment failed : your order has been closed for a timeout.");
					$order->addStatusHistoryComment(__("Payment failed : your order has been closed for a timeout."))
						->save();
					$response_url = $this->base_url . 'checkout/onepage/failure';
					$this->messageManager->addError(__("Payment failed : your order has been closed for a timeout."));
					$response->setUrl($response_url);
					return $response;
				}

				if (!$order->hasData()) {
					$this->logger->info("Error : Order with the id : " . $order_id . "is not found!");
					$response_url = $this->base_url . 'checkout/onepage/failure';
					$this->messageManager->addError(__("Payment failed : Order with the id : " . $order_id . "is not found!"));
					$response->setUrl($response_url);
					return $response;
					//die("Fatal Error: Order with the id of " . $order_id . " is not found!");
				}

				
				// check if it is a succssful tranaction
				$order->setData('nbe_resultIndicator', $resultIndicator);
				//$order->setData('nbe_successIndicator', '767657ef77ce48a8');
				$order->save();
				$nbeResult = $order->getData('nbe_resultIndicator');
				$nbeSuccess = $order->getData('nbe_successIndicator');
				if ($nbeResult != $nbeSuccess) {
					$this->logger->info("Order Payment failed : mismatch indicators between resultIndicator: " . $nbeResult . " and successIndicator: " . $nbeSuccess);
					$order->addStatusHistoryComment(__("Order Payment failed : mismatch indicators between resultIndicator: " . $nbeResult . " and successIndicator: " . $nbeSuccess))
						->save();
					$response_url = $this->base_url . 'checkout/onepage/failure';
					$this->messageManager->addError(__("Error : Payment failed. mismatch indicators for the Order with id : " . $order_id));
					$response->setUrl($response_url);
					return $response;
					// ready to invoice	
				} else {
					$this->logger->info("Order Payment Accepted : match indicators between resultIndicator: " . $nbeResult . " and successIndicator: " . $nbeSuccess);
					$order->addStatusHistoryComment(__("Order Payment Accepted : match indicators between resultIndicator: " . $nbeResult . " and successIndicator: " . $nbeSuccess))
						->save();
					if (!$order->hasInvoices()) {
						$grandTotal = $order->getGrandTotal();
						//$amount_cents = $obj['amount_cents'] / 100.0;
						$currency = $order->getOrderCurrencyCode();
						$order->addStatusHistoryComment(__("Order Payment Accepted: Customer Paid ($grandTotal $currency)."))
							->save();

						$invoice = $this->invoice->prepareInvoice($order);
						$invoice->setGrandTotal($grandTotal)
							->setBaseGrandTotal($grandTotal)
							->register()
							->pay()
							->save();
						$this->logger->info("Order succssfully invoiced");
						$order->addStatusHistoryComment(__("Order succssfully invoiced"))
							->save();
						$order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING)
							->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING)
							->save();
						$this->logger->info("Order succssfully change state to PROCESSING.");
						$order->addStatusHistoryComment(__("Order succssfully change state to PROCESSING."))
							->save();
						// trigger ERP system
						//$this->eventManager->dispatch('paymob_create_magento_invoice_after', ['orderId' => $order_id]);

						$response_url = $this->base_url . 'checkout/onepage/success';
						$this->messageManager->addSuccess(__("Payment successful for order " . $order_id));

						$response->setUrl($response_url);
						return $response;
					} else {
						$this->logger->info("Error: Order with the id of : " . $order_id . " already invoiced!");
						$order->addStatusHistoryComment(__("Error: Order with the id of : " . $order_id . " already invoiced!"))
							->save();
						$response_url = $this->base_url . 'checkout/onepage/failure';
						$this->messageManager->addError(__("Error: Order with the id of : " . $order_id . " already invoiced!"));
						$response->setUrl($response_url);
						return $response;
						//die("Fatal Error: Order with the id of ($order_id) already invoices!");
					}
				}
			} else {
				$this->logger->info("This Server is not ready to handle your request right now.");
				echo "This Server is not ready to handle your request right now.";
				die(1);
			}
		} catch (\Exception $e) {
			// echo "====================================";
			// echo $e->getMessage();
			// echo "====================================";
			// die(1);
			$this->logger->info("Error: " . $e);
			$response_url = $this->base_url . 'checkout/onepage/failure';
			$this->messageManager->addError(__("Error: something went wrong please try again"));
			$response = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
			$response->setUrl($response_url);
			return $response;
		}
	}
}
