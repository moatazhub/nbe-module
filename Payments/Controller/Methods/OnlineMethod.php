<?php

namespace Ahly\Payments\Controller\Methods;
//use Fkra\Test1\Helper\Notify;
use Ahly\Payments\Helper\Accepting;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;

class OnlineMethod extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Model\Order $order,
        \Ahly\Payments\Model\Online $online,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->resultFactory = $context->getResultFactory();
        $this->order = $order;
        $this->online = $online;
        $this->base_url = $storeManager->getStore()->getBaseUrl();
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
        //$logger->info('Debuggging on!');

        $this->response = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON);
        $this->response->setHttpResponseCode(201);
        $order_id = $this->getRequest()->getContent();
        
        $order = $this->order->load($order_id);
        $logger->info("============================");
        $logger->info($order_id);
        $logger->info($this->order->getIncrementId());
        $logger->info("============================");
        try {
            $order = $this->order->load($order_id);

            $config = [
                "api_user" => $this->online->getConfigData('api_user'),
                "api_password" => $this->online->getConfigData('api_password'),
            ];

            $helper = new Accepting($order, $config, $this->base_url);
            // if (!$helper->valid_currency($this->api_id)) {
            //     throw new \Exception($helper->get_error_response("Store currency is not supported by this payment method.", "DEFAULT"));
            // }
            $helper->set_session();
            if (!$helper->get_session()) {
                $logger->info("error");
                throw new \Exception($helper->get_error_response("Can't obtain auth session.", "DEFAULT"));
            }
            $order->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT)
                ->setStatus(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT)
                ->addStatusHistoryComment(__("Order Created: Awaiting payment"))
                ->save();

            $this->response->setData([
                'success' => true,
                'session_id' => $helper->get_session(),
                'order_id' => $order->getIncrementId(),
                'order_amount' => $order->getGrandTotal(),
            ]);
        } catch (\Exception $e) {
            $this->response->setData([
                'success' => false,
                'detail' => $e->getMessage(),
            ]);
        }



        return $this->response;
    }
}
