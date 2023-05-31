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
 * @copyright  Copyright (c) 2018-2020 BSS Commerce Co. ( http://bsscommerce.com )
 * @license    http://bsscommerce.com/Bss-Commerce-License.txt
 */

namespace Bss\ConfiguableGridView\Helper;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Swatches\Helper\Data as SwatchData;
use Magento\Swatches\Helper\Media as SwatchMediaHelper;
use Bss\ConfiguableGridView\Model\ResourceModel\Product\Type\Configurable as ResourceTypeConfigurable;
use Magento\Framework\App\ObjectManager;

/**
 * Class HelperClass
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class HelperClass
{
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * @var \Magento\Framework\Filter\LocalizedToNormalized
     */
    protected $localizedToNormalized;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $resolverInterface;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $loggerInterface;

    /**
     * @var \Magento\Checkout\Helper\Cart
     */
    protected $helperCart;

    /**
     * @var \Magento\Framework\Escaper
     */
    protected $escaper;

    /**
     * @var \Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory
     */
    protected $stockItemCriteriaFactory;

    /**
     * @var \Magento\CatalogInventory\Api\StockItemRepositoryInterface
     */
    protected $stockItemRepository;

    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @var SwatchData
     */
    protected $swatchDataHelper;

    /**
     * @var SwatchMediaHelper
     */
    protected $swatchMediaHelper;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $jsonEncoder;

    /**
     * @var ResourceTypeConfigurable
     */
    protected $resourceTypeConfigurable;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * @var mixed
     */
    protected $stockResolver;

    /**
     * HelperClass constructor.
     *
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Framework\Filter\LocalizedToNormalized $localizedToNormalized
     * @param \Magento\Framework\Locale\ResolverInterface $resolverInterface
     * @param \Psr\Log\LoggerInterface $loggerInterface
     * @param \Magento\Checkout\Helper\Cart $helperCart
     * @param \Magento\Framework\Escaper $escaper
     * @param \Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory $stockItemCriteriaFactory
     * @param \Magento\CatalogInventory\Api\StockItemRepositoryInterface $stockItemRepository
     * @param ProductMetadataInterface $productMetadata
     * @param SwatchData $swatchDataHelper
     * @param SwatchMediaHelper $swatchMediaHelper
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param ResourceTypeConfigurable $resourceTypeConfigurable
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Framework\Filter\LocalizedToNormalized $localizedToNormalized,
        \Magento\Framework\Locale\ResolverInterface $resolverInterface,
        \Psr\Log\LoggerInterface $loggerInterface,
        \Magento\Checkout\Helper\Cart $helperCart,
        \Magento\Framework\Escaper $escaper,
        \Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory $stockItemCriteriaFactory,
        \Magento\CatalogInventory\Api\StockItemRepositoryInterface $stockItemRepository,
        ProductMetadataInterface $productMetadata,
        SwatchData $swatchDataHelper,
        SwatchMediaHelper $swatchMediaHelper,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        ResourceTypeConfigurable $resourceTypeConfigurable,
        \Magento\Framework\Module\Manager $moduleManager
    ) {
        $this->productFactory = $productFactory;
        $this->localizedToNormalized = $localizedToNormalized;
        $this->resolverInterface = $resolverInterface;
        $this->loggerInterface = $loggerInterface;
        $this->helperCart = $helperCart;
        $this->escaper = $escaper;
        $this->stockItemCriteriaFactory = $stockItemCriteriaFactory;
        $this->stockItemRepository = $stockItemRepository;
        $this->productMetadata = $productMetadata;
        $this->swatchDataHelper = $swatchDataHelper;
        $this->swatchMediaHelper = $swatchMediaHelper;
        $this->jsonEncoder = $jsonEncoder;
        $this->resourceTypeConfigurable = $resourceTypeConfigurable;
        $this->moduleManager = $moduleManager;
    }

    /**
     * Return Product
     *
     * @return \Magento\Catalog\Model\ProductFactory
     */
    public function returnProductFactory()
    {
        return $this->productFactory;
    }

    /**
     * Return Localized To Normalized
     *
     * @return \Magento\Framework\Filter\LocalizedToNormalized
     */
    public function returnLocalizedToNormalized()
    {
        return $this->localizedToNormalized;
    }

    /**
     * Return Resolver Interface
     *
     * @return \Magento\Framework\Locale\ResolverInterface
     */
    public function returnResolverInterface()
    {
        return $this->resolverInterface;
    }

    /**
     * Return Logger Interface
     *
     * @return \Psr\Log\LoggerInterface
     */
    public function returnLoggerInterface()
    {
        return $this->loggerInterface;
    }

    /**
     * Return Helper Cart
     *
     * @return \Magento\Checkout\Helper\Cart
     */
    public function returnHelperCart()
    {
        return $this->helperCart;
    }

    /**
     * Return Escape
     *
     * @return \Magento\Framework\Escaper
     */
    public function returnEscaper()
    {
        return $this->escaper;
    }

    /**
     * Return Stock Item CriteriaFactory
     *
     * @return \Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory
     */
    public function returnStockItemCriteriaFactory()
    {
        return $this->stockItemCriteriaFactory;
    }

    /**
     * Return Stock Item Repository
     *
     * @return \Magento\CatalogInventory\Api\StockItemRepositoryInterface
     */
    public function returnStockItemRepository()
    {
        return $this->stockItemRepository;
    }

    /**
     * Return Product Meta data
     *
     * @return ProductMetadataInterface
     */
    public function returnProductMetadata()
    {
        return $this->productMetadata;
    }

    /**
     * Return swatch Data helper object
     *
     * @return SwatchData
     */
    public function getSwatchDataHelper()
    {
        return $this->swatchDataHelper;
    }

    /**
     * Get swatch media helper object
     *
     * @return SwatchMediaHelper
     */
    public function getSwatchMediaHelper()
    {
        return $this->swatchMediaHelper;
    }

    /**
     * Get json encoder object
     *
     * @return \Magento\Framework\Json\EncoderInterface
     */
    public function getJsonEncoder()
    {
        return $this->jsonEncoder;
    }

    /**
     * Get stock resolver object
     *
     * @return \Magento\CatalogInventory\Api\StockItemRepositoryInterface
     */
    public function getStockResolver()
    {
        if ($this->moduleManager->isEnabled('Magento_Inventory')) {
            $this->stockResolver = ObjectManager::getInstance()->get(StockResolverInterface::class);
            return $this->stockResolver;
        }
        return $this->stockItemRepository;
    }

    /**
     * Get Configurable Type Resource
     *
     * @return ResourceTypeConfigurable
     */
    public function getResourceTypeConfigurable()
    {
        return $this->resourceTypeConfigurable;
    }
}
