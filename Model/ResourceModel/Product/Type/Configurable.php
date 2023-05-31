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

namespace Bss\ConfiguableGridView\Model\ResourceModel\Product\Type;

use Bss\ConfiguableGridView\Helper\Data;
use Bss\ConfiguableGridView\Model\ResourceModel\Product\InventoryStock;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Sql\ExpressionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Zend_Db_Statement_Exception;

/**
 * Configurable
 */
class Configurable
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * @var InventoryStock
     */
    private $inventoryStock;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var ExpressionFactory
     */
    protected $expressionFactory;

    /**
     * @var Data
     */
    private $helperData;

    /**
     * Configurable constructor.
     * @param StoreManagerInterface $storeManager
     * @param ProductMetadataInterface $productMetadata
     * @param InventoryStock $inventoryStock
     * @param ResourceConnection $resource
     * @param ExpressionFactory $expressionFactory
     * @param Data $helperData
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ProductMetadataInterface $productMetadata,
        InventoryStock $inventoryStock,
        ResourceConnection $resource,
        ExpressionFactory $expressionFactory,
        Data $helperData
    ) {
        $this->storeManager = $storeManager;
        $this->productMetadata = $productMetadata;
        $this->inventoryStock = $inventoryStock;
        $this->resource = $resource;
        $this->expressionFactory = $expressionFactory;
        $this->helperData = $helperData;
    }

    /**
     * After get used
     *
     * @param \Magento\ConfigurableProduct\Model\Product\Type\Configurable $subject
     * @param mixed $result
     * @return mixed
     * @throws LocalizedException
     * @throws Zend_Db_Statement_Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetUsedProductCollection(
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable $subject,
        $result
    ) {
        $helper = $this->helperData;
        $version = $this->productMetadata->getVerSion();
        if (version_compare($version, '2.3.0') >= 0) {
            $websiteCode = $this->storeManager->getWebsite()->getCode();
            $checkExistTable = $this->isTableExistsOrNot(
                $this->resource->getTableName('inventory_stock_sales_channel')
            );
            if ($checkExistTable) {
                $stockId = $this->inventoryStock->getStockIdByWebsiteCode($websiteCode);
                if ($stockId) {
                    $stockTable = $this->inventoryStock->getStockTableName($stockId);

                    $result->getSelect()->joinInner(
                        ['stock' => $stockTable],
                        'stock.sku = e.sku'
                    );

                    $connection = $this->resource->getConnection();
                    $table_sales_stock = $this->resource->getTableName('inventory_reservation');
                    $query = $connection->select()->from($table_sales_stock, ['quantity' => 'SUM(quantity)'])
                        ->where('sku = e.sku')
                        ->where('stock_id = ?', $stockId)
                        ->limit(1);
                    $result->getSelect()->columns([
                        'quantity_bss' => $this->expressionFactory->create(
                            ['expression' => 'IFNULL((' . $query . '),0) + stock.quantity']
                        )
                    ]);
                }
                // issue: https://github.com/magento/magento2/issues/22379
                if ($helper->isShowConfig('out_stock')) {
                    $result->setFlag('has_stock_status_filter', true);
                }
            }
        }

        return $result;
    }

    /**
     * Check table exists or not Query
     *
     * @param string $nameTable
     * @return bool
     */
    public function isTableExistsOrNot($nameTable)
    {
        $connection=$this->resource->getConnection();
        $tableName=$this->resource->getTableName($nameTable);
        return $connection->isTableExists($tableName);
    }
}
