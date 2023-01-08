<?php

namespace Ahly\Payments\Controller\Callback;

use Ahly\Payments\Helper\Notify;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
//use Magento\Framework\Event\ManagerInterface;

class Online extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{

	// /**
	//  * @var ManagerInterface
	//  */
	// private $eventManager;

	public function __construct(
		\Magento\Framework\App\Action\Context $context,
		\Magento\Sales\Model\Order $order,
		\Magento\Sales\Model\Service\InvoiceService $invoice,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Framework\App\ResourceConnection $resource,
		\Magento\Customer\Model\Customer $customer,
		\Ahly\Payments\Model\Online $online,
		\Magento\Framework\App\Request\Http $request
	) {
		parent::__construct($context);
		$this->context = $context;
		$this->order = $order;
		$this->invoice = $invoice;
		$this->resultFactory = $context->getResultFactory();
		$this->base_url = $storeManager->getStore()->getBaseUrl();
		$this->website_id = $storeManager->getStore()->getWebsiteId();
		$this->resource = $resource;
		$this->customer = $customer;
		$this->online = $online;
		$this->request = $request;
	}

	public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
	{
		return null;
	}

	public function validateForCsrf(RequestInterface $request): ?bool
	{
		return true;
	}

	public function execute()
	{
		
		$writer = new \Zend_Log_Writer_Stream(BP . '/var/log/nbe.log');
		$logger = new \Zend_Log();
		$logger->addWriter($writer);
		//$logger->info('Debuggging response...??');
		try {

			//die("Fatal Error: Order with the id of was not found!");
			$id = $this->request->getParam('orderid');
			$resultIndicator = $this->request->getParam('resultIndicator');
			$isCancel = $this->request->getParam('cancel');
			$isTimeout = $this->request->getParam('timeout');
			
			// echo $id;
			// $logger->info($id);
			// $logger->info('hellooooo');

			$ping = new Notify(
				$this->order,
				$this->invoice,
				$this->resultFactory,
				$this->resource,
				$this->customer,
				$this->messageManager,
				$this->base_url,
				$this->website_id,
				$this->_eventManager,
				$logger

			);
			
			return $ping->Pong($id, $resultIndicator, $isCancel, $isTimeout);
		} catch (\Exception $e) {
			\var_dump($e);
			die(1);
		}
	}
}
