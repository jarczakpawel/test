<?php
/**
 * BSS Commerce Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://bsscommerce.com/Bss-Commerce-License.txt
 *
 * @category   BSS
 * @package    Bss_ConfiguableGridView
 * @author     Extension Team
 * @copyright  Copyright (c) 2018-2019 BSS Commerce Co. ( http://bsscommerce.com )
 * @license    http://bsscommerce.com/Bss-Commerce-License.txt
 */
namespace Bss\ConfiguableGridView\Model\ResourceModel\Product;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;

class InventoryStock extends AbstractDb
{
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resources;

    /**
     * InventoryStock constructor.
     * @param Context $context
     * @param null $connectionName
     */
    public function __construct(
        Context $context
    ) {
        $this->resources = $context->getResources();
    }

    /**
     * Construct
     * @return void
     */
    public function _construct()
    {
        $this->_init('inventory_stock_sales_channel', 'stock_id');
    }

    /**
     * @param string $websiteCode
     * @return bool|mixed
     * @throws \Zend_Db_Statement_Exception
     */
    public function getStockIdByWebsiteCode($websiteCode)
    {
        $data = [];
        $connection = $this->resources->getConnection();
        $channelTable = $this->resources->getTableName('inventory_stock_sales_channel');
        $select = $connection->select()
            ->from(
                $channelTable,
                ['stock_id']
            )->where('code = ? and type = "website"', $websiteCode);

        $query = $connection->query($select);

        while ($row = $query->fetch()) {
            array_push($data, $row);
        }
        if (!empty($data)) {
            return $data[0]['stock_id'];
        }

        return false;
    }

    /**
     * @param int $stockId
     * @return string
     */
    public function getStockTableName($stockId)
    {
        return $this->resources->getTableName('inventory_stock_' . $stockId);
    }
}
