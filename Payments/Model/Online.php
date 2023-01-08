<?php
namespace Ahly\Payments\Model;

use Magento\Payment\Model\Method\AbstractMethod;

class Online extends AbstractMethod
{
    const CODE = "AhlyOnline";

    protected $_code = self::CODE;
    protected $_isGateway = true;
    protected $_canCapture = false;
    protected $_canCapturePartial = false;
    protected $_canRefund = false;
    protected $_canRefundInvoicePartial = false;
}
