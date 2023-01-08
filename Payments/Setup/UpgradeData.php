<?php
 
namespace Ahly\Payments\Setup;
 
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Sales\Setup\SalesSetupFactory;
 
/**
 * Class UpgradeData
 *
 * @package Ahly\Payments\Setup
 */
class UpgradeData implements UpgradeDataInterface
{
    private $salesSetupFactory;
 
    /**
     * Constructor
     *
     * @param \Magento\Sales\Setup\SalesSetupFactory $salesSetupFactory
     */
    public function __construct(SalesSetupFactory $salesSetupFactory)
    {
        $this->salesSetupFactory = $salesSetupFactory;
    }
 
    /**
     * {@inheritdoc}
     */
    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        if (version_compare($context->getVersion(), "2.0.0", "<")) {
            $salesSetup = $this->salesSetupFactory->create(['setup' => $setup]);
            $salesSetup->addAttribute(
                'order',
                'nbe_successIndicator', 
                [
                    'type' => 'varchar',
                    'length' => 5,
                    'visible' => false,
                    'required' => false,
                    'grid' => true
                ]
            );
            $salesSetup->addAttribute(
                'order',
                'nbe_resultIndicator',
                [
                    'type' => 'varchar',
                    'length' => 5,
                    'visible' => false,
                    'required' => false,
                    'grid' => true
                ]
            );
        }
    }
}