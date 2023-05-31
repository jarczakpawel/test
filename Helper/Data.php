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
 * @copyright  Copyright (c) 2018-2021 BSS Commerce Co. ( http://bsscommerce.com )
 * @license    http://bsscommerce.com/Bss-Commerce-License.txt
 */
namespace Bss\ConfiguableGridView\Helper;

use Magento\Customer\Model\SessionFactory;
use Magento\Directory\Model\PriceCurrency;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\DesignInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Data
 *
 * @package Bss\ConfiguableGridView\Helper
 */
class Data extends AbstractHelper
{
    const XML_PATH_ADVANCED_TIER_PRICE = "configuablegridview/general/advanced_tier_price";
    const XML_PATH_TOOLTIP_TIER_PRICE = "configuablegridview/general/tooltip_tier_price";
    const XML_PATH_TABLE_TIER_PRICE = "configuablegridview/general/table_tier_price";

    const PRE_ORDER_MODULE_NAME = 'Bss_PreOrder';
    const PRE_ORDER_ENABLE_CONFIG_PATH = 'preorder/general/enable';

    /**
     * @var null
     */
    protected $infoPrice = null;
    /**
     * @return ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @return \Magento\Framework\Pricing\Helper\Data
     */
    public $price;

    /**
     * @return PriceCurrency
     */
    public $currency;

    /**
     * @return Registry
     */
    public $registry;

    /**
     * @return RequestInterface
     */
    protected $request;

    /**
     * @return SessionFactory
     */
    public $customer;

    /**
     * @return ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var DesignInterface
     */
    protected $design;

    /**
     * Data constructor.
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Pricing\Helper\Data $price
     * @param PriceCurrency $currency
     * @param Registry $registry
     * @param RequestInterface $request
     * @param ProductMetadataInterface $productMetadata
     * @param SessionFactory $customer
     * @param StoreManagerInterface $storeManager
     * @param DesignInterface $design
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Pricing\Helper\Data $price,
        PriceCurrency $currency,
        Registry $registry,
        RequestInterface $request,
        ProductMetadataInterface $productMetadata,
        SessionFactory $customer,
        StoreManagerInterface $storeManager,
        DesignInterface $design
    ) {
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig;
        $this->price = $price;
        $this->currency = $currency;
        $this->registry = $registry;
        $this->request = $request;
        $this->customer = $customer;
        $this->productMetadata = $productMetadata;
        $this->storeManager = $storeManager;
        $this->design = $design;
    }

    /**
     * Enabled or Disable module
     *
     * @param int|null $customerGroupId
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function isEnabled($customerGroupId = null)
    {
        $active = $this->scopeConfig->getValue('configuablegridview/general/active', ScopeInterface::SCOPE_STORE);
        if ($active == 1) {
            $disabled_customer_group = $this->scopeConfig->getValue(
                'configuablegridview/general/disabled_customer_group',
                ScopeInterface::SCOPE_STORE
            );
            if ($disabled_customer_group == '') {
                return true;
            }
            $disabled_customer_group = explode(',', $disabled_customer_group);
            if ($customerGroupId == null) {
                $customerGroupId = $this->customer->create()->getCustomerGroupId();
            }
            if (!in_array($customerGroupId, $disabled_customer_group)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Is Show config
     *
     * @param string $config
     * @return bool
     */
    public function isShowConfig($config)
    {
        $active = $this->scopeConfig->getValue('configuablegridview/general/' . $config, ScopeInterface::SCOPE_STORE);
        if ($active != 1) {
            return false;
        }

        return true;
    }

    /**
     * Can show Unit
     *
     * @return bool
     */
    public function canShowUnit()
    {
        return $this->scopeConfig->getValue(
            'configuablegridview/general/unit_price',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Display Price with Currency
     *
     * @param int $price
     * @return mixed
     */
    public function getDisplayPriceWithCurrency($price)
    {
        return $this->price->currency($price, true, false);
    }

    /**
     * Get currency Symbol
     *
     * @return mixed
     */
    public function getCurrencySymbol()
    {
        return $this->currency->getCurrencySymbol();
    }

    /**
     * Get currency Product
     *
     * @return mixed
     */
    public function getCurrentProduct()
    {
        return $this->registry->registry('current_product');
    }
    /**
     * Get currency url
     *
     * @return string
     */
    public function getCurrentUrl()
    {
        $moduleName = $this->request->getModuleName();
        $controllerName = $this->request->getControllerName();
        $requestActionName = $this->request->getActionName();
        return $moduleName . '_' . $controllerName . '_' . $requestActionName;
    }

    /**
     * Is magento version
     *
     * @return bool
     */
    public function isMagentoVersion()
    {
        $version = $this->productMetadata->getVersion();
        if (version_compare($version, '2.1.6') >= 0) {
            return true;
        }
        return false;
    }

    /**
     * Get Display Attribute Advanced
     *
     * @param string $column
     * @return string
     */
    public function getDisplayAttributeAdvanced($column)
    {
        $class = '';
        $display_types = ['mobile', 'tablet'];
        foreach ($display_types as $display_type) {
            $display = $this->scopeConfig->getValue(
                'configuablegridview/general/' . $display_type,
                ScopeInterface::SCOPE_STORE
            );
            $resultArr = explode(',', $display);
            if (!empty($resultArr) && in_array($column, $resultArr)) {
                $class .= ' bss-' . $display_type . '-hide';
            }
        }
        return $class;
    }

    /**
     * Get add to cart button template
     *
     * @param string $name
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getAddtocartButtonTemplate($name)
    {
        if ($this->isConfigurableProduct()) {
            return $name;
        }
        return 'Magento_Catalog::product/view/addtocart.phtml';
    }

    /**
     * Get form template
     *
     * @param string $name
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getFormTemplate($name)
    {
        if ($this->isConfigurableProduct()) {
            return $name;
        }
        return 'Magento_Catalog::product/view/form.phtml';
    }

    /**
     * Get config table Tier price
     *
     * @param null/int $storeId
     * @return int
     */
    public function tableTierPrice($storeId = null)
    {
        return $this->scopeConfig->getValue(self::XML_PATH_TABLE_TIER_PRICE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get config tooltip tier price
     *
     * @param null/int $storeId
     * @return mixed
     */
    public function tooltipTierPrice($storeId = null)
    {
        return $this->scopeConfig->getValue(self::XML_PATH_TOOLTIP_TIER_PRICE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get config advanced tier price
     *
     * @param null/int $storeId
     * @return mixed
     */
    public function advancedTierPrice($storeId = null)
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ADVANCED_TIER_PRICE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get type tax
     *
     * @return mixed
     */
    public function getTypeTax()
    {
        return $this->scopeConfig->getValue("tax/calculation/price_includes_tax");
    }

    /**
     * Get format price
     *
     * @param double $amount
     * @return float|string
     */
    public function getFormatPrice($amount)
    {
        return $this->currency->convertAndFormat($amount, true);
    }

    /**
     * Display type of price
     *
     * @param int|null $storeId
     *
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getPriceDisplayType($storeId = null)
    {
        if (!$storeId) {
            $storeId = $this->storeManager->getStore()->getId();
        }
        return (int) $this->scopeConfig->getValue(
            \Magento\Tax\Model\Config::CONFIG_XML_PATH_PRICE_DISPLAY_TYPE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get store manager object
     *
     * @return StoreManagerInterface
     */
    public function getStoreManager()
    {
        return $this->storeManager;
    }

    /**
     * Get scope config object
     *
     * @return ScopeConfigInterface
     */
    public function getScopeConfig()
    {
        return $this->scopeConfig;
    }

    /**
     * If active theme is smartwave porto
     *
     * @return bool
     */
    public function isPortoThemeActive()
    {
        return $this->design->getDesignTheme()->getCode() === "Smartwave/porto";
    }

    /**
     * Whatever we will apply this package to check compatible between pre_order and cp_grid
     *
     * @return bool
     */
    public function isEnableCompatiblePreOrderPackage()
    {
        return $this->_moduleManager->isEnabled(self::PRE_ORDER_MODULE_NAME) &&
            $this->scopeConfig->isSetFlag(self::PRE_ORDER_ENABLE_CONFIG_PATH, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Check if product is pre order and product has status when out of stock
     *
     * @param $product
     * @return bool
     */
    public function isPreOrderSalableQty($product)
    {
        $preOrderStatus = $product->getPreorder();
        return $this->isEnableCompatiblePreOrderPackage() && $product->getPreorder() == 2;
    }

    /**
     * Check is configurable product enable
     *
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function isConfigurableProduct() {
        if ($this->isEnabled()) {
            $currencyProduct = $this->registry->registry('current_product');
            if($currencyProduct) {
                $disableGridView = $currencyProduct->getDisableGridTableView();
                if($currencyProduct->getTypeId() == "configurable" && !$disableGridView) {
                    return true;
                }
            }
        }
        return false;
    }
}
