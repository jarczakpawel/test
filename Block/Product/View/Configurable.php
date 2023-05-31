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
namespace Bss\ConfiguableGridView\Block\Product\View;

use Bss\ConfiguableGridView\Helper\Data as HelperData;
use Bss\ConfiguableGridView\Helper\DataProcessor;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Model\ProductFactory;
use Magento\Checkout\Helper\Cart as HelperCart;
use Magento\Checkout\Model\SessionFactory;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Module\Manager;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\ArrayUtils;
use Magento\Framework\UrlInterface;
use Magento\Swatches\Model\SwatchAttributesProvider;
use Magento\Tax\Helper\Data;

/**
 * Configurable product view
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Configurable extends \Magento\Swatches\Block\Product\Renderer\Configurable
{
    /**
     * Prices display settings
     */
    const CONFIG_XML_PATH_PRICE_DISPLAY_TYPE = 'tax/display/type';

    /**
     * @var HelperCart
     */
    protected $helperCart;

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var ProductFactory
     */
    protected $modelProduct;

    /**
     * @var Manager
     */
    protected $manager;

    /**
     * @var DataProcessor
     */
    protected $dataProcessor;

    /**
     * @var Data
     */
    private $dataTax;

    /**
     * @var SessionFactory
     */
    protected $_checkoutSession;

    /**
     * @var UrlInterface
     */
    protected $urlInterface;

    /**
     * Configurable constructor.
     *
     * @param UrlInterface $urlInterface
     * @param Context $context
     * @param HelperCart $helperCart
     * @param ArrayUtils $arrayUtils
     * @param CurrentCustomer $currentCustomer
     * @param PriceCurrencyInterface $priceCurrency
     * @param ProductFactory $modelProduct
     * @param Manager $manager
     * @param Data $dataTax
     * @param SessionFactory $session
     * @param DataProcessor $dataProcessor
     * @param array $data
     * @param SwatchAttributesProvider|null $swatchAttributesProvider
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        UrlInterface $urlInterface,
        Context $context,
        HelperCart $helperCart,
        ArrayUtils $arrayUtils,
        CurrentCustomer $currentCustomer,
        PriceCurrencyInterface $priceCurrency,
        ProductFactory $modelProduct,
        Manager $manager,
        Data $dataTax,
        SessionFactory $session,
        DataProcessor $dataProcessor,
        array $data = [],
        SwatchAttributesProvider $swatchAttributesProvider = null
    ) {
        $this->urlInterface = $urlInterface;
        $this->helperCart = $helperCart;
        $this->helperData = $dataProcessor->getModuleHelper();
        $this->modelProduct = $modelProduct;
        $this->manager = $manager;
        $this->dataTax = $dataTax;
        $this->_checkoutSession = $session;
        $this->dataProcessor = $dataProcessor;
        parent::__construct(
            $context,
            $arrayUtils,
            $dataProcessor->getHelperClass()->getJsonEncoder(),
            $dataProcessor,
            $dataProcessor->getCatalogProductHelper(),
            $currentCustomer,
            $priceCurrency,
            $dataProcessor->getConfigurableProductAttribute(),
            $dataProcessor->getSwatchDataHelper(),
            $dataProcessor->getSwatchMediaHelper(),
            $data,
            $swatchAttributesProvider
        );
    }

    /**
     * Get Render Template
     *
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function getRendererTemplate()
    {
        $helper = $this->helperData;
        if ($helper->isMagentoVersion()) {
            $hasSwatch = $this->isProductHasSwatchAttribute();
        } else {
            $hasSwatch = $this->isProductHasSwatchAttribute;
        }
        if ($helper->isEnabled()) {
            if ($hasSwatch) {
                if ($helper->isEnableCompatiblePreOrderPackage()) {
                    return 'Bss_ConfiguableGridView::product/view/swatch/pre_configurable.phtml';
                }
                return 'Bss_ConfiguableGridView::product/view/swatch/configurable.phtml';
            }
            if ($helper->isEnableCompatiblePreOrderPackage()) {
                return 'Bss_ConfiguableGridView::product/view/pre_configurable.phtml';
            }
            return 'Bss_ConfiguableGridView::product/view/configurable.phtml';
        }

        return $hasSwatch ? self::SWATCH_RENDERER_TEMPLATE : self::CONFIGURABLE_RENDERER_TEMPLATE;
    }

    /**
     * Get AllowProducts
     *
     * @return Product[]|mixed
     */
    public function getAllowProducts()
    {
        if (!$this->hasAllowProducts()) {
            $this->setAllowProducts(
                $this->dataProcessor->getAllAllowProducts($this->getProduct())
            );
        }
        return $this->getData('allow_products');
    }

    /**
     * Get data configurable grid view
     *
     * @param null|string $label
     * @return array
     * @throws NoSuchEntityException|LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getConfiguableGridViewData($label = null)
    {
        return $this->dataProcessor->getConfigurableGridViewData($this->getProduct(), $label);
    }

    /**
     * Get current version
     *
     * @return mixed
     */
    public function getCurrentVersion()
    {
        return $this->dataProcessor->getCurrentVersion();
    }

    /**
     * Get json attribute label config
     *
     * @return mixed
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getJsonAttrLabelConfig()
    {
        $config = $this->getConfiguableGridViewData(true);
        return $this->jsonEncoder->encode($config);
    }

    /**
     * Load product by store id
     *
     * @param int $id
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getLoadProduct($id)
    {
        $storeId = $this->_storeManager->getStore()->getId();
        return $this->modelProduct->create()->setStoreId($storeId)->load($id);
    }

    /**
     * Check display both prices
     *
     * @return bool
     */
    public function isDisplayBothPrices()
    {
        return $this->dataTax->displayBothPrices();
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
        return $this->dataProcessor->tableTierPrice($dataChildProduct);
    }

    /**
     * Save percent Tier price
     *
     * @param array $dataChildProduct
     * @return mixed
     */
    public function savePercent($dataChildProduct)
    {
        return $this->dataProcessor->savePercent($dataChildProduct);
    }

    /**
     * Delete duplicate tier price
     *
     * @param array $tierPrices
     * @return mixed
     */
    public function uniqueTierPrice($tierPrices)
    {
        return $this->dataProcessor->uniqueTierPrice($tierPrices);
    }

    /**
     * Data Child Product add attribute
     *
     * @return array
     * @throws NoSuchEntityException|LocalizedException
     */
    public function dataChildProduct()
    {
        return $this->dataProcessor->dataChildProduct();
    }

    /**
     * Advanced Tier Price
     *
     * @param array $dataChildProduct
     * @return mixed
     */
    public function advancedTierPrice($dataChildProduct)
    {
        return $this->dataProcessor->advancedTierPrice($dataChildProduct);
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
        return $this->dataProcessor->firstAdvancedTierPrice($dataChildProduct, $datum, $key, $i);
    }

    /**
     * Tooltip show message Tier price
     *
     * @param array $advancedTierPrice
     * @return int|string
     * @throws NoSuchEntityException
     */
    public function messageTierPrice($advancedTierPrice)
    {
        return $this->dataProcessor->messageTierPrice($advancedTierPrice, $this->getProduct());
    }

    /**
     * Message tier Price when enable advanced tier price
     *
     * @param array $advancedTierPrice
     * @return string
     */
    public function messageAdvanceTierPrice($advancedTierPrice)
    {
        return $this->dataProcessor->messageAdvanceTierPrice($advancedTierPrice);
    }

    /**
     * Default tier price magento 2
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function defaultTierPrice()
    {
        return $this->dataProcessor->defaultTierPrice($this->getProduct());
    }

    /**
     * Data product include advanced tier price
     *
     * @param array $advancedTierPrice
     * @return array
     */
    public function dataProduct($advancedTierPrice)
    {
        return $this->dataProcessor->getDataProduct($advancedTierPrice);
    }

    /**
     * Json data Product
     *
     * @param array $dataProduct
     * @return string
     */
    public function jsonDataProduct($dataProduct)
    {
        return $this->jsonEncoder->encode($dataProduct);
    }

    /**
     * Get config Advanced Tier Price
     *
     * @return mixed
     */
    public function configATPrice()
    {
        return $this->helperData->advancedTierPrice($this->getCurrentStore()->getStoreId());
    }

    /**
     * Config table tier price
     *
     * @return mixed
     */
    public function configTableTierPrice()
    {
        return $this->helperData->tableTierPrice($this->getCurrentStore()->getStoreId());
    }

    /**
     * Class HelperData
     *
     * @return HelperData
     */
    public function helperData()
    {
        return $this->helperData;
    }

    /**
     * Class Helper Cart
     *
     * @return mixed
     */
    public function helperCart()
    {
        return $this->_cartHelper;
    }

    /**
     * Convert price
     *
     * @param double $amount
     */
    public function convertPrice($amount)
    {
        $this->priceCurrency->convertAndFormat($amount);
    }

    /**
     * Price Tax
     *
     * @param array $data
     * @return mixed
     */
    public function priceTax($data)
    {
        $typeTax = $this->getTypeTax();
        if ($typeTax == 1) {
            $price = $data["price"];
        } else {
            $price = $data["price_excl_tax"];
        }
        return $price;
    }

    /**
     * Get type Tax
     *
     * @return mixed
     */
    public function getTypeTax()
    {
        return $this->helperData->getTypeTax();
    }

    /**
     * Delete tier price incorrect
     *
     * @param array $dataProduct
     * @return array
     */
    public function cleanTierPrice($dataProduct)
    {
        return $this->dataProcessor->cleanTierPrice($dataProduct);
    }

    /**
     * Enable grid table view by attribute enable_grid_table_view
     *
     * @return mixed
     */
    public function disableGirdView()
    {
        return $this->getProduct()->getDisableGridTableView();
    }

    /**
     * Return template swatch or not swatch configurable product
     *
     * @return string
     */
    public function getTemplateCP()
    {
        $helper = $this->helperData;
        if ($helper->isMagentoVersion()) {
            $hasSwatch = $this->isProductHasSwatchAttribute();
        } else {
            $hasSwatch = $this->isProductHasSwatchAttribute;
        }
        return $hasSwatch ? self::SWATCH_RENDERER_TEMPLATE : self::CONFIGURABLE_RENDERER_TEMPLATE;
    }

    /**
     * Produce and return block's html output
     *
     * @return string
     */
    public function toHtmlCP()
    {
        $this->setTemplate(
            $this->getTemplateCP()
        );

        return $this->_toHtml();
    }

    /**
     * Get price display type
     *
     * @return int
     * @throws NoSuchEntityException
     */
    public function getPriceDisplayType()
    {
        return (int)$this->helperData->getPriceDisplayType();
    }

    /**
     * Get quote
     *
     * @return \Magento\Checkout\Model\Session
     */
    public function getQuote()
    {
        return $this->_checkoutSession->create()->getQuote();
    }

    /**
     * Check module Bss_PreOrder
     *
     * @return bool
     */
    public function checkModuleBssPreOrder()
    {
        return $this->manager->isEnabled('Bss_PreOrder');
    }

    /**
     * Get url ajax
     *
     * @return string
     */
    public function getUrlAjax()
    {
        return $this->urlInterface->getUrl('configurableGrid/index/index');
    }

    /**
     * Get html Max Price;
     *
     * @param array $assocProductData
     * @return mixed|string
     */
    public function getHtmlMaxPrice($assocProductData)
    {
        return $this->dataProcessor->getHtmlMaxPrice($assocProductData);
    }

    /**
     * Get html min Price;
     *
     * @param array $assocProductData
     * @return mixed|string
     */
    public function getHtmlMinPrice($assocProductData)
    {
        return $this->dataProcessor->getHtmlMinPrice($assocProductData);
    }

    /**
     * Get all products
     *
     * @param ProductInterface $product
     *
     * @return mixed
     */
    public function getAllProducts($product)
    {
        return $product->getTypeInstance()->getUsedProducts($this->getProduct());
    }

    /**
     * Get min Price
     *
     * @return mixed
     */
    public function getMinPrice()
    {
        return $this->dataProcessor->getMinPrice();
    }

    /**
     * Get max price
     *
     * @return mixed
     */
    public function getMaxPrice()
    {
        return $this->dataProcessor->getMaxPrice();
    }

    /**
     * Get currency Symbol
     *
     * @return mixed
     */
    public function getCurrencySymbol()
    {
        return $this->helperData->getCurrencySymbol();
    }

    /**
     * Is Show config
     *
     * @param string $config
     * @return bool
     */
    public function isShowConfig($config)
    {
        return $this->helperData->isShowConfig($config);
    }

    /**
     * Can show unit price
     *
     * @return bool
     */
    public function canShowUnit()
    {
        return $this->dataProcessor->canShowUnit();
    }
}
