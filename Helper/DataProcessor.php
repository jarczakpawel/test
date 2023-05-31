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

use Bss\ConfiguableGridView\Helper\Data as HelperData;
use Bss\ConfiguableGridView\Model\ResourceModel\Product\Type\Configurable;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Helper\Product as CatalogProductHelper;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Image\UrlBuilder;
use Magento\Catalog\Model\ProductFactory;
use Magento\CatalogInventory\Api\Data\StockItemInterface as StockItemInterfaceAlias;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\ConfigurableProduct\Model\ConfigurableAttributeData;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable\Attribute;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Swatches\Helper\Data as SwatchData;
use Magento\Swatches\Helper\Media;
use Magento\Swatches\Model\Swatch;
use Magento\Tax\Pricing\Adjustment;
use Magento\Framework\App\ObjectManager;

/**
 * Processing data
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @since 100.0.2
 */
class DataProcessor extends \Magento\ConfigurableProduct\Helper\Data
{
    /**
     * Status product in stock
     */
    const IS_IN_STOCK = 1;

    /**
     * Status product out stock
     */
    const IS_OUT_STOCK = 0;

    /**
     * Status config enable backorder
     */
    const ENABLE_BACKORDER = 1;

    /**
     * Status config disable backorder
     */
    const DISABLE_BACKORDER = 0;

    /**
     * @var null
     */
    protected $attributeLabel = null;

    /**
     * @var Product
     */
    private $product = null;
    /**
     * @var LayoutInterface
     */
    protected $layout;

    /**
     * @var ConfigurableAttributeData
     */
    protected $mageConfigurableAttrData;

    /**
     * @var StockRegistryInterface
     */
    protected $stockRegistry;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var HelperClass
     */
    protected $helperClass;

    /**
     * @var StockResolverInterface
     */
    protected $stockResolver;

    /**
     * @var null
     */
    protected $allProducts = null;
    /**
     * @var null
     */
    protected $infoPrice = null;

    /**
     * @var null
     */
    protected $assocProductData = null;

    /**
     * @var array
     */
    private $allowProducts;

    /**
     * @var CatalogProductHelper
     */
    protected $catalogProductHelper;

    /**
     * @var HelperData
     */
    protected $moduleHelper;

    /**
     * @var SwatchData
     */
    protected $swatchHelper;

    /**
     * @var Media
     */
    protected $swatchMediaHelper;

    /**
     * @var UrlBuilder
     */
    protected $imageUrlBuilder;

    /**
     * @var EncoderInterface
     */
    protected $jsonEncoder;

    /**
     * @var Configurable
     */
    protected $resourceTypeConfigurable;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * DataProcessor constructor.
     *
     * @param LayoutInterface $layout
     * @param CatalogProductHelper $catalogProductHelper
     * @param Data $moduleHelper
     * @param ConfigurableAttributeData $mageConfigurableAttrData
     * @param StockRegistryInterface $stockRegistry
     * @param ProductRepositoryInterface $productRepository
     * @param HelperClass $helperClass
     * @param ImageHelper $imageHelper
     * @param UrlBuilder|null $imageUrlBuilder
     * @param \Magento\Framework\Module\Manager $moduleManager
     */
    public function __construct(
        LayoutInterface $layout,
        CatalogProductHelper $catalogProductHelper,
        HelperData $moduleHelper,
        ConfigurableAttributeData $mageConfigurableAttrData,
        StockRegistryInterface $stockRegistry,
        ProductRepositoryInterface $productRepository,
        HelperClass $helperClass,
        ImageHelper $imageHelper,
        UrlBuilder $imageUrlBuilder,
        \Magento\Framework\Module\Manager $moduleManager
    ) {
        parent::__construct(
            $imageHelper,
            $imageUrlBuilder
        );
        $this->layout = $layout;
        $this->catalogProductHelper = $catalogProductHelper;
        $this->moduleHelper = $moduleHelper;
        $this->mageConfigurableAttrData = $mageConfigurableAttrData;
        $this->stockRegistry = $stockRegistry;
        $this->productRepository = $productRepository;
        $this->helperClass = $helperClass;
        $this->swatchHelper = $helperClass->getSwatchDataHelper();
        $this->swatchMediaHelper = $helperClass->getSwatchMediaHelper();
        $this->imageUrlBuilder = $imageUrlBuilder;
        $this->jsonEncoder = $helperClass->getJsonEncoder();
        $this->stockResolver = $helperClass->getStockResolver();
        $this->resourceTypeConfigurable = $helperClass->getResourceTypeConfigurable();
        $this->moduleManager = $moduleManager;
    }

    /**
     * Data product include advanced tier price
     *
     * @param array $advTierPrice
     *
     * @return array
     */
    public function getDataProduct($advTierPrice)
    {
        $data = [];
        foreach ($advTierPrice as $key => $item) {
            $data[$key]["price"] = $item["price"];
            if (isset($item["advanced_tier_price"])) {
                $data[$key]["advanced_tier_price"] = $item["advanced_tier_price"];
                sort($data[$key]["advanced_tier_price"]["product_ids"]);
                $data[$key]["qty"] = $item["advanced_tier_price"]["qty"];
                $data[$key]["advanced"] = 0;
            }
            if (isset($item["tier_price"])) {
                $i = 0;
                foreach ($item["tier_price"] as $keyTierPrice => $tierPrice) {
                    $data[$key]["tier_price"][$i] = $tierPrice;
                    $data[$key]["tier_price"][$i]["qty"] = $keyTierPrice;
                    $i++;
                }
            }
            $data[$key]["buy_qty"] = 0;
            $data[$key]["buy_price"] = 0;
            $data[$key]["buy_price_excl_tax"] = 0;
            $data[$key]["qty_advanced"] = 0;
            $data[$key]["product_id"] = $item["product_id"];
        }
        return $data;
    }

    /**
     * Get allow products
     *
     * @param ProductInterface $product
     *
     * @return Product[]
     */
    public function getAllowProducts($product)
    {
        if (!$this->allowProducts) {
            $this->allowProducts = $this->getAllAllowProducts($product);
        }
        return $this->allowProducts;
    }

    /**
     * Get all allow products
     *
     * @param ProductInterface $product
     *
     * @return array
     */
    public function getAllAllowProducts($product)
    {
        $products = [];
        $skipSaleableCheck = $this->catalogProductHelper->getSkipSaleableCheck();
        if (!$this->allProducts) {
            $this->allProducts = $product->getTypeInstance()->getUsedProducts($product, null);
        }
        $helper = $this->moduleHelper;
        foreach ($this->allProducts as $product) {
            if ($helper->isShowConfig('stock_availability') && $helper->isShowConfig('out_stock')) {
                $products[] = $product;
            } else {
                if ($product->isSaleable() || $skipSaleableCheck) {
                    $products[] = $product;
                }
            }
        }
        return $products;
    }

    /**
     * Get configurable grid view data for product
     *
     * @param Product|ProductInterface $currentProduct
     * @param string|null $label
     *
     * @return array
     * @throws NoSuchEntityException
     * @throws InputException
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getConfigurableGridViewData($currentProduct, $label = null)
    {
        if(!$this->assocProductData) {
            $storeManager = $this->moduleHelper->getStoreManager();
            $storeId = $storeManager->getStore()->getId();
            $options = $this->getOptions($currentProduct, $this->getAllowProducts($currentProduct));
            $attributesData = $this->mageConfigurableAttrData->getAttributesData($currentProduct, $options);
            $websiteId = $storeManager->getWebsite()->getCode();
            $associatedProductData = [];
            $attrLabel = [];
            $sort = false;
            foreach ($this->getAllowProducts($currentProduct) as $product) {
                $helper = $this->moduleHelper;
                $stockItem = $this->stockRegistry->getStockItem(
                    $product->getId(),
                    $product->getStore()->getWebsiteId()
                );
                $stockStatus = $stockItem->getIsInStock();
                $backOrder = $stockItem->getBackorders();
                if ($this->moduleManager->isEnabled('Magento_Inventory')) {
                    $stockId = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $websiteId)->getStockId();
                    $salableQty = ObjectManager::getInstance()->get(GetProductSalableQtyInterface::class)->execute($product->getSku(), $stockId);
                    if ($salableQty <= 0 && !$helper->isShowConfig('out_stock')) {
                        continue;
                    }
                } else {
                    $stockId = $stockItem->getStockId();
                    $salableQty = $stockItem->getQty();
                    if (!$stockItem->getIsInStock() && !$helper->isShowConfig('out_stock')) {
                        continue;
                    }
                }
                if ($product->getStatus() == Status::STATUS_DISABLED) {
                    continue;
                }

                if ($salableQty > 40) { // Pokazuje max 40 produktów.
                    $salableQty = 40;
                }

                $data = $options['index'][$product->getId()];
                $data['subtotal'] = 0;
                $data['qty'] = 0;
                $data['sku'] = $product->getSku();
                $data['stock'] = $this->getStockData($product, $stockItem);
                $data['stock']['qty'] = $salableQty;
                $data['stock']['stock_id'] = (string)$stockId;
                $data['stock']['is_in_stock'] = $this->getStatusSaleableQty($salableQty, $backOrder, $stockStatus);
                $data['product_id'] = $product->getId();
                $data['preorder'] = '';
                $data["disable_add_to_cart"] = false;
                $data['back_order'] = $backOrder;
                if ($this->moduleHelper->isModuleOutputEnabled('Bss_HidePrice')) {
                    $data["disable_add_to_cart"] = $product->getDisableAddToCart();
                }
                if ($this->isBssPreOrderModuleEnabled()) {
                    $data['preorder'] = $product->getResource()
                        ->getAttributeRawValue($product->getId(), 'preorder', $storeId);
                }
                $data['price'] = [
                    'old_price' => $product->getPriceInfo()->getPrice('regular_price')->getAmount()->getValue(),
                    'basePrice' => $product->getPriceInfo()->getPrice('final_price')->getAmount()->getBaseAmount(),
                    'finalPrice' => $product->getPriceInfo()->getPrice('final_price')->getAmount()->getValue(),
                    'excl_tax' => $product->getPriceInfo()->getPrice('final_price')->getAmount()->getValue(
                        Adjustment::ADJUSTMENT_CODE
                    ),
                    'productPrice' => $product->getPriceInfo()->getPrice('final_price')->getAmount()->getValue()
                ];
                if ($this->moduleHelper->getPriceDisplayType() == 1) {
                    $data['price']['finalPrice'] = $product->getPriceInfo()->getPrice('final_price')->getAmount()->getValue(
                        Adjustment::ADJUSTMENT_CODE
                    );
                }
                $data['key_sort'] = '';
                $this->getOptionsData(
                    $product,
                    $options,
                    $sort,
                    $data,
                    $attributesData,
                    $attrLabel
                );

                $this->getTierPriceData($product, $data);
                $product->setDisableButtonAddToQuote(true);
                $data["html_unit_price"] = $this->getPriceHtml($product, 'final_price');
                $associatedProductData[] = $data;
            }
            $this->attributeLabel = $attrLabel;
            $this->assocProductData = $this->returnAssociatedProduct($attributesData, $associatedProductData);
        }
        if ($label && $this->attributeLabel) {
            return $this->attributeLabel;
        }

        return $this->assocProductData;
    }

    /**
     *  Processing get option data
     *
     * @param Product $product
     * @param array $options
     * @param bool $sort
     * @param array $data
     * @param array $attributesData
     * @param array $attrLabel
     * @retrun void
     */
    private function getOptionsData($product, $options, &$sort, &$data, &$attributesData, &$attrLabel)
    {
        foreach ($options['index'][$product->getId()] as $key => $option) {
            if (!$sort) {
                $sort = $key;
            }
            $search = array_search($option, array_column($attributesData['attributes'][$key]['options'], 'id'));
            $data['attributes'][$key] = $attributesData['attributes'][$key]['options'][$search];
            $data['attributes'][$key]['code'] = $attributesData['attributes'][$key]['code'];
            $data['attributes'][$key]['attributeId'] = $key;
            $attrLabel[$key][$data['attributes'][$key]['id']]['label'] = $data['attributes'][$key]['label'];
            $data['key_sort'] .= $option;
        }
    }

    /**
     * Module pre-order enabled
     *
     * @return bool
     */
    public function isBssPreOrderModuleEnabled()
    {
        return $this->moduleHelper->isModuleOutputEnabled('Bss_PreOrder');
    }

    /**
     * Get status of product by saleable qty
     *
     * @param int $saleableQty
     * @param int $backOrder
     * @param int $stockStatus
     * @return int
     */
    public function getStatusSaleableQty($saleableQty, $backOrder, $stockStatus)
    {
        if ($stockStatus == self::IS_IN_STOCK) {
            if ($backOrder != self::DISABLE_BACKORDER) {
                return self::IS_IN_STOCK;
            } elseif ($saleableQty > 0) {
                return self::IS_IN_STOCK;
            }
        }
        return self::IS_OUT_STOCK;
    }

    /**
     * Get product salable quantity
     *
     * @param Product $product
     * @param StockItemInterfaceAlias $stockItem
     * @return array
     */
    private function getStockData($product, $stockItem)
    {
        $stock = $stockItem->getData();
        if ($this->resourceTypeConfigurable->isTableExistsOrNot("inventory_stock_sales_channel") &&
            version_compare($this->getCurrentVersion(), '2.3.0') >= 0) {
            $stock['qty'] = $product->getQuantityBss();
        }
        return $stock;
    }

    /**
     * Get current version
     *
     * @return string
     */
    public function getCurrentVersion()
    {
        return $this->helperClass->returnProductMetadata()->getVersion();
    }

    /**
     * Get Data Tier Price
     *
     * @param Product $product
     * @param array $data
     * @throws NoSuchEntityException
     */
    protected function getTierPriceData($product, &$data)
    {
        $tierPricesList = $product->getPriceInfo()->getPrice('tier_price')->getTierPriceList();
        if (!empty($tierPricesList)) {
            foreach ($tierPricesList as $tierPrice) {
                $tierPriceQty = $tierPrice['price_qty'];
                $data['tier_price'][$tierPriceQty]['price'] = $tierPrice['price']->getValue();
                $data['tier_price'][$tierPriceQty]['qty'] = $tierPriceQty;
                $price = $tierPrice['price']->getValue(Adjustment::ADJUSTMENT_CODE);
                if ($this->moduleHelper->getPriceDisplayType() == 1) {
                    $data['tier_price'][$tierPriceQty]['price'] = $price;
                }
                $data['tier_price'][$tierPriceQty]['price_excl_tax'] = $price;
            }
        }
    }

    /**
     * Return assocProduct
     *
     * @param array $attributesData
     * @param array $associatedProductData
     * @return array
     */
    protected function returnAssociatedProduct($attributesData, $associatedProductData)
    {
        $productsData = [];
        if ($attributes = $this->sortAttribute($attributesData['attributes'])) {
            foreach ($attributes as $value) {
                foreach ($associatedProductData as $data) {
                    if ($value == $data['key_sort']) {
                        $productsData[] = $data;
                    }
                }
            }
        }
        return $productsData;
    }

    /**
     * Sort Attribute data
     *
     * @param array $data
     * @return array|null
     */
    protected function sortAttribute($data)
    {
        $arr = [];
        foreach ($data as $key => $value) {
            if ($value['options']) {
                foreach ($value['options'] as $item) {
                    $arr[$key][] = $item['id'];
                }
            }
        }
        $attributes = array_values($arr);
        $newAttr = null;
        $count = count($attributes);
        if ($count == 1) {
            return $attributes[0];
        }
        for ($i = 0; $i < $count - 1; $i++) {
            if (!$newAttr) {
                $newAttr = $this->sort($attributes[$i], $attributes[$i + 1]);
            } else {
                $newAttr = $this->sort($newAttr, $attributes[$i + 1]);
            }
        }
        return $newAttr;
    }

    /**
     * Sort element A element B
     *
     * @param array $elementA
     * @param array $elementB
     * @return array
     */
    private function sort($elementA, $elementB)
    {
        $temp = [];
        foreach ($elementA as $valueA) {
            foreach ($elementB as $valueB) {
                $temp[] = $valueA . $valueB;
            }
        }
        return $temp;
    }

    /**
     * Data Child Product add attribute
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function dataChildProduct()
    {
        $data = $this->assocProductData;
        foreach ($data as $key => $datum) {
            $indexAttribute = 0;
            foreach ($datum["attributes"] as $attribute) {
                if ($indexAttribute == 0) {
                    $data[$key]["attribute"] = $attribute["label"];
                } else {
                    $data[$key]["attribute"] .= "-" . $attribute["label"];
                }
                $indexAttribute++;
            }
        }
        return $this->cleanTierPrice($data);
    }

    /**
     * Delete tier price incorrect
     *
     * @param array $dataProduct
     * @return array
     */
    public function cleanTierPrice($dataProduct)
    {
        foreach ($dataProduct as $keyProduct => $product) {
            if (isset($product["tier_price"])) {
                $price = 0;
                foreach ($product["tier_price"] as $keyTP => $tierPrice) {
                    if ($price == 0 || $tierPrice["price"] <= $price) {
                        $price = $tierPrice["price"];
                        continue;
                    } else {
                        unset($dataProduct[$keyProduct]["tier_price"][$keyTP]);
                    }
                }
            }
        }
        return $dataProduct;
    }

    /**
     * Table show tier price
     *
     * @param array $dataChildProduct
     * @return array|null
     * @throws NoSuchEntityException
     */
    public function tableTierPrice($dataChildProduct)
    {
        $storeId = $this->moduleHelper->getStoreManager()->getStore()->getId();
        $configTableTierPrice = $this->moduleHelper->tableTierPrice($storeId);
        if ($configTableTierPrice == 0) {
            return null;
        }
        $dataChildProduct = $this->savePercent($dataChildProduct);
        $tierPrices = [];
        $i = 0;
        foreach ($dataChildProduct as $key => $datum) {
            if (isset($datum["tier_price"])) {
                foreach ($datum["tier_price"] as $keyTierPrice => $tierPrice) {
                    $tierPrices[$i]["tier_price"] = $tierPrice;
                    $tierPrices[$i]["attribute"] = $datum["attribute"];
                    $tierPrices[$i]["qty"] = $keyTierPrice;
                    $i++;
                }
            }
        }
        $tierPrices = $this->uniqueTierPrice($tierPrices);
        $tableTierPrice = [];
        $i = 0;
        foreach ($tierPrices as $key => $tierPrice) {
            if (!isset($tierPrice["delete"])) {
                foreach ($tierPrice as $item) {
                    $tableTierPrice[$i]["price"] = $this->moduleHelper->getFormatPrice($item["price"]);
                    $tableTierPrice[$i]["price_excl_tax"] = $this->moduleHelper->getFormatPrice($item["price_excl_tax"]);
                    $tableTierPrice[$i]["save"] = $item["save"] . "%";
                    break;
                }
                if (isset($tierPrice["attributes_tier_price"])) {
                    $tableTierPrice[$i]["attributes_tier_price"] = $tierPrice["attributes_tier_price"];
                } else {
                    $tableTierPrice[$i]["attributes_tier_price"][0] = $tierPrice["attribute"];
                }
                $tableTierPrice[$i]["qty"] = $tierPrice["qty"];
                $i++;
            }
        }
        return $tableTierPrice;
    }

    /**
     * Delete duplicate tier price
     *
     * @param array $tierPrices
     * @return array
     */
    public function uniqueTierPrice($tierPrices)
    {
        foreach ($tierPrices as $key => $tierPrice) {
            if (isset($tierPrices[$key]["delete"])) {
                continue;
            }
            $j = 0;
            foreach ($tierPrices as $key1 => $tierPrice1) {
                if ($key1 != $key) {
                    if ($tierPrice["tier_price"] == $tierPrice1["tier_price"] && $tierPrice["qty"] == $tierPrice1["qty"]) {
                        if ($j == 0) {
                            $tierPrices[$key]["attributes_tier_price"][$j] = $tierPrice["attribute"];
                            $j++;
                        }
                        $tierPrices[$key]["attributes_tier_price"][$j] = $tierPrice1["attribute"];
                        $tierPrices[$key1]["delete"] = 1;
                        $j++;
                    }
                }
            }
        }
        return $tierPrices;
    }

    /**
     * Save percent Tier price
     *
     * @param array $dataChildProduct
     * @return array
     */
    public function savePercent($dataChildProduct)
    {
        foreach ($dataChildProduct as $key => $datum) {
            if (isset($datum["tier_price"])) {
                foreach ($datum["tier_price"] as $keyTierPrice => $tierPrice) {
                    $save = 100 - round($tierPrice["price_excl_tax"] / $datum["price"]["excl_tax"] * 100);
                    $dataChildProduct[$key]["tier_price"][$keyTierPrice]["save"] = $save;
                }
            }
        }
        return $dataChildProduct;
    }

    /**
     * Get Swatch config data
     *
     * @param ProductInterface|Product $currentProduct
     *
     * @return string
     */
    public function getJsonSwatchConfig($currentProduct)
    {
        $this->product = $currentProduct;
        $attributesData = $this->getSwatchAttributesData($currentProduct);
        $allOptionIds = $this->getConfigurableOptionsIds($attributesData, $currentProduct);
        $swatchesData = $this->swatchHelper->getSwatchesByOptionsId($allOptionIds);

        $config = [];
        foreach ($attributesData as $attributeId => $attributeDataArray) {
            if (isset($attributeDataArray['options'])) {
                $config[$attributeId] = $this->addSwatchDataForAttribute(
                    $attributeDataArray['options'],
                    $swatchesData,
                    $attributeDataArray
                );
            }
            if (isset($attributeDataArray['additional_data'])) {
                $config[$attributeId]['additional_data'] = $attributeDataArray['additional_data'];
            }
        }

        return $this->jsonEncoder->encode($config);
    }

    /**
     * Get configurable options ids.
     *
     * @param array $attributeData
     * @param ProductInterface|Product $currentProduct
     * @return array
     * @since 100.0.3
     */
    protected function getConfigurableOptionsIds(array $attributeData, $currentProduct)
    {
        $ids = [];
        foreach ($this->getAllowProducts($currentProduct) as $product) {
            /** @var Attribute $attribute */
            foreach ($this->getAllowAttributes($currentProduct) as $attribute) {
                $productAttribute = $attribute->getProductAttribute();
                $productAttributeId = $productAttribute->getId();
                if (isset($attributeData[$productAttributeId])) {
                    $ids[$product->getData($productAttribute->getAttributeCode())] = 1;
                }
            }
        }
        return array_keys($ids);
    }

    /**
     * Get swatch attributes data.
     *
     * @param ProductInterface $product
     * @return array
     */
    protected function getSwatchAttributesData($product)
    {
        return $this->swatchHelper->getSwatchAttributesAsArray($product);
    }

    /**
     * Add Swatch Data for attribute
     *
     * @param array $options
     * @param array $swatchesCollectionArray
     * @param array $attributeDataArray
     * @return array
     */
    protected function addSwatchDataForAttribute(
        array $options,
        array $swatchesCollectionArray,
        array $attributeDataArray
    )
    {
        $result = [];
        foreach ($options as $optionId => $label) {
            if (isset($swatchesCollectionArray[$optionId])) {
                $result[$optionId] = $this->extractNecessarySwatchData($swatchesCollectionArray[$optionId]);
                $result[$optionId] = $this->addAdditionalMediaData($result[$optionId], $optionId, $attributeDataArray);
                $result[$optionId]['label'] = $label;
            }
        }

        return $result;
    }

    /**
     * Retrieve Swatch data for config
     *
     * @param array $swatchDataArray
     * @return array
     */
    protected function extractNecessarySwatchData(array $swatchDataArray)
    {
        $result['type'] = $swatchDataArray['type'];

        if ($result['type'] == Swatch::SWATCH_TYPE_VISUAL_IMAGE && !empty($swatchDataArray['value'])) {
            $result['value'] = $this->swatchMediaHelper->getSwatchAttributeImage(
                Swatch::SWATCH_IMAGE_NAME,
                $swatchDataArray['value']
            );
            $result['thumb'] = $this->swatchMediaHelper->getSwatchAttributeImage(
                Swatch::SWATCH_THUMBNAIL_NAME,
                $swatchDataArray['value']
            );
        } else {
            $result['value'] = $swatchDataArray['value'];
        }

        return $result;
    }

    /**
     * Add media from variation
     *
     * @param array $swatch
     * @param integer $optionId
     * @param array $attributeDataArray
     * @return array
     */
    protected function addAdditionalMediaData(array $swatch, $optionId, array $attributeDataArray)
    {
        if (isset($attributeDataArray['use_product_image_for_swatch'])
            && $attributeDataArray['use_product_image_for_swatch']
        ) {
            $variationMedia = $this->getVariationMedia($attributeDataArray['attribute_code'], $optionId);
            if (!empty($variationMedia)) {
                $swatch['type'] = Swatch::SWATCH_TYPE_VISUAL_IMAGE;
                $swatch = array_merge($swatch, $variationMedia);
            }
        }
        return $swatch;
    }

    /**
     * Generate Product Media array
     *
     * @param string $attributeCode
     * @param integer $optionId
     * @return array
     */
    protected function getVariationMedia($attributeCode, $optionId)
    {
        $variationMediaArray = [];
        if ($this->product) {
            $variationProduct = $this->swatchHelper->loadFirstVariationWithSwatchImage(
                $this->product,
                [$attributeCode => $optionId]
            );

            if (!$variationProduct) {
                $variationProduct = $this->swatchHelper->loadFirstVariationWithImage(
                    $this->product,
                    [$attributeCode => $optionId]
                );
            }


            if ($variationProduct) {
                $variationMediaArray = [
                    'value' => $this->getSwatchProductImage($variationProduct, Swatch::SWATCH_IMAGE_NAME),
                    'thumb' => $this->getSwatchProductImage($variationProduct, Swatch::SWATCH_THUMBNAIL_NAME),
                ];
            }
        }

        return $variationMediaArray;
    }

    /**
     * Get swatch product image.
     *
     * @param Product|ProductInterface $childProduct
     * @param string $imageType
     * @return string
     */
    protected function getSwatchProductImage(Product $childProduct, $imageType)
    {
        if ($this->isProductHasImage($childProduct, Swatch::SWATCH_IMAGE_NAME)) {
            $swatchImageId = $imageType;
            $imageAttributes = ['type' => Swatch::SWATCH_IMAGE_NAME];
        } elseif ($this->isProductHasImage($childProduct, 'image')) {
            $swatchImageId = $imageType == Swatch::SWATCH_IMAGE_NAME ? 'swatch_image_base' : 'swatch_thumb_base';
            $imageAttributes = ['type' => 'image'];
        }

        if (!empty($swatchImageId) && !empty($imageAttributes['type'])) {
            return $this->imageUrlBuilder->getUrl($childProduct->getData($imageAttributes['type']), $swatchImageId);
        }
    }

    /**
     * Check if product have image.
     *
     * @param Product $product
     * @param string $imageType
     * @return bool
     */
    protected function isProductHasImage(Product $product, $imageType)
    {
        return $product->getData($imageType) !== null && $product->getData($imageType) != SwatchData::EMPTY_IMAGE_VALUE;
    }

    /**
     * Advanced Tier Price
     *
     * @param array $dataChildProduct
     * @return mixed
     */
    public function advancedTierPrice($dataChildProduct)
    {
        foreach ($dataChildProduct as $key => $datum) {
            if (isset($datum["tier_price"])) {
                $i = 0;
                foreach ($dataChildProduct as $key1 => $datum1) {
                    if ($key != $key1 && isset($datum1["tier_price"])) {
                        if ($datum["tier_price"] == $datum1["tier_price"]) {
                            $dataChildProduct = $this->firstAdvancedTierPrice($dataChildProduct, $datum, $key, $i);
                            $dataChildProduct[$key]["advanced_tier_price"]["product_ids"][$datum1["product_id"]]
                                = $datum1["product_id"];
                            $i++;
                            $dataChildProduct[$key]["advanced_tier_price"]["message"] .= ", " . $datum1["attribute"];
                            $dataChildProduct[$key]["advanced_tier_price"]["attributes"] .= ", " . $datum1["attribute"];
                        }
                    }
                }
            }
        }
        return $dataChildProduct;
    }

    /**
     * Get data when first child product advanced tier price
     *
     * @param array $dataChildProduct
     * @param array $datum
     * @param int $key
     * @param int $i
     * @return mixed
     */
    public function firstAdvancedTierPrice($dataChildProduct, $datum, $key, &$i)
    {
        if ($i == 0) {
            $i++;
            $dataChildProduct[$key]["advanced_tier_price"]["tier_price"] = $datum["tier_price"];
            $dataChildProduct[$key]["advanced_tier_price"]["product_id"] = $datum["product_id"];
            $dataChildProduct[$key]["advanced_tier_price"]["product_ids"][$datum["product_id"]]
                = $datum["product_id"];
            foreach ($datum["tier_price"] as $qty => $tierPrice) {
                $dataChildProduct[$key]["advanced_tier_price"]["qty"] = $qty;
                $dataChildProduct[$key]["advanced_tier_price"]["price"] = $tierPrice["price"];
                $dataChildProduct[$key]["advanced_tier_price"]["price_excl_tax"]
                    = $tierPrice["price_excl_tax"];
                $dataChildProduct[$key]["advanced_tier_price"]["message"]
                    = "Buy total " . $qty . " ";

                break;
            }
            $dataChildProduct[$key]["advanced_tier_price"]["message"] .= $datum["attribute"];
            $dataChildProduct[$key]["advanced_tier_price"]["attributes"] = $datum["attribute"];
        }
        return $dataChildProduct;
    }

    /**
     * Tooltip show message Tier price
     *
     * @param array $advancedTierPrice
     * @param Product $currentProduct
     * @return int|string
     * @throws NoSuchEntityException
     */
    public function messageTierPrice($advancedTierPrice, $currentProduct)
    {
        $storeId = $this->moduleHelper->getStoreManager()->getStore()->getId();
        $configTooltipTierPrice = $this->moduleHelper->tooltipTierPrice($storeId);
        if ($configTooltipTierPrice == 0) {
            return 0;
        } elseif ($this->moduleHelper->advancedTierPrice($storeId) == 0) {
            return $this->defaultTierPrice();
        }
        return $this->messageAdvanceTierPrice($advancedTierPrice);
    }

    /**
     * Default tier price magento 2
     *
     * @return string
     */
    public function defaultTierPrice()
    {
        $dataChildProduct = $this->assocProductData;
        $message = [];
        $i = 0;
        foreach ($dataChildProduct as $item) {
            if (isset($item["tier_price"])) {
                $j = 0;
                foreach ($item["tier_price"] as $key => $tierPrice) {
                    $save = 100 - round($tierPrice["price_excl_tax"] / $item["price"]["excl_tax"] * 100);
                    if ($save < 0) {
                        continue;
                    }
                    $price = $tierPrice["price"];
                    $message[$i][$j] = __(
                        "Buy total %1 for %2 each and save %3 %",
                        $key,
                        $this->moduleHelper->getFormatPrice($price),
                        $save
                    )->render();
                    $j++;
                }
            } else {
                $message[$i] = -1;
            }
            $i++;
        }
        return $this->jsonEncoder->encode($message);
    }

    /**
     * Message tier Price when enable advanced tier price
     *
     * @param array $advancedTierPrice
     * @return string
     */
    public function messageAdvanceTierPrice($advancedTierPrice)
    {
        $message = [];
        $i = 0;
        foreach ($advancedTierPrice as $item) {
            if (isset($item["tier_price"])) {
                $j = 0;
                foreach ($item["tier_price"] as $key => $tierPrice) {
                    if (isset($item["advanced_tier_price"])) {
                        $price = $tierPrice["price"];
                        $message[$i][$j] = __(
                            "Buy total %1 %2 for price %3 each ",
                            $key,
                            $item["advanced_tier_price"]["attributes"],
                            $this->moduleHelper->getFormatPrice($price)
                        )->render();
                    } else {
                        $save = 100 - round($tierPrice["price_excl_tax"] / $item["price"]["excl_tax"] * 100);
                        $price = $tierPrice["price"];
                        $message[$i][$j] = __(
                            "Buy total %1 for %2 each and save %3%",
                            $key,
                            $this->moduleHelper->getFormatPrice($price),
                            $save
                        )->render();
                    }
                    $j++;
                }
            } else {
                $message[$i] = -1;
            }
            $i++;
        }
        return $this->jsonEncoder->encode($message);
    }

    /**
     * Get escaper object
     *
     * @return Escaper
     */
    public function getEscaper()
    {
        return $this->helperClass->returnEscaper();
    }

    /**
     * Get module data helper
     *
     * @return HelperData
     */
    public function getModuleHelper()
    {
        return $this->moduleHelper;
    }

    /**
     * Get product repository object
     *
     * @return ProductRepositoryInterface
     */
    public function getProductRepository()
    {
        return $this->productRepository;
    }

    /**
     * Get swatch helper
     *
     * @return SwatchData
     */
    public function getSwatchDataHelper()
    {
        return $this->swatchHelper;
    }

    /**
     * Get swatch media helper
     *
     * @return Media
     */
    public function getSwatchMediaHelper()
    {
        return $this->swatchMediaHelper;
    }

    /**
     * Get helper class
     *
     * @return HelperClass
     */
    public function getHelperClass()
    {
        return $this->helperClass;
    }

    /**
     * Get catalog product helper
     *
     * @return CatalogProductHelper
     */
    public function getCatalogProductHelper()
    {
        return $this->catalogProductHelper;
    }

    /**
     * Get configurable product attribute
     *
     * @return ConfigurableAttributeData
     */
    public function getConfigurableProductAttribute()
    {
        return $this->mageConfigurableAttrData;
    }

    /**
     * Get html price
     *
     * @param ProductFactory $product
     * @param null/string $priceType
     * @param array $arguments
     * @return string
     */
    public function getPriceHtml($product, $priceType = null, array $arguments = [])
    {
        if (!isset($arguments['zone'])) {
            $arguments['zone'] = 'item_view';
        }

        $priceRender = $this->layout->getBlock('product.price.render.default');

        $price = '';
        if ($priceRender && $priceType) {
            $price = $priceRender->render(
                $priceType,
                $product,
                $arguments
            );
        }
        return $price;
    }

    /**
     * Get html Min Price;
     *
     * @param $assocProductData
     * @return mixed|string
     */
    public function getHtmlMinPrice($assocProductData)
    {
        $productId = array_search($this->getMinPrice(), $this->getInfoPrice());
        foreach ($assocProductData as $product) {
            if ($product["product_id"] == $productId) {
                return $product["html_unit_price"];
            }
        }
        return '';
    }

    /**
     * Get html Max Price;
     *
     * @param array $assocProductData
     * @return mixed|string
     */
    public function getHtmlMaxPrice($assocProductData)
    {
        $productId = array_search($this->getMaxPrice(), $this->getInfoPrice());
        foreach ($assocProductData as $product) {
            if ($product["product_id"] == $productId) {
                return $product["html_unit_price"];
            }
        }
        return '';
    }

    /**
     * Get info price
     *
     * @return array
     */
    public function getInfoPrice()
    {
        if (!$this->infoPrice) {
            if (!$this->allProducts) {
                $product = $this->moduleHelper->getCurrentProduct();
                $this->allProducts = $product->getTypeInstance()->getUsedProducts($product);
            }
            $array = [];
            foreach ($this->allProducts as $product) {
                $array[$product->getId()] = $product->getFinalPrice();
            }
            $this->infoPrice = $array;
        }
        return $this->infoPrice;
    }

    /**
     * Get max Price
     *
     * @return mixed
     */
    public function getMaxPrice()
    {
        if (!$this->infoPrice) {
            $this->infoPrice = $this->getInfoPrice();
        }
        return max($this->infoPrice);
    }

    /**
     * Get min price
     *
     * @return mixed
     */
    public function getMinPrice()
    {
        if (!$this->infoPrice) {
            $this->infoPrice = $this->getInfoPrice();
        }
        return min($this->infoPrice);
    }

    /**
     * Can show unit
     *
     * @return bool
     */
    public function canShowUnit(){
        $configUnit = $this->moduleHelper->canShowUnit();
        if ($configUnit == 1) {
            return true;
        }
        if ($configUnit == 2 && (float)$this->getMaxPrice() != (float)$this->getMinPrice()) {
            return true;
        }
        return false;
    }
}
