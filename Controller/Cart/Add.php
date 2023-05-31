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

namespace Bss\ConfiguableGridView\Controller\Cart;

use Bss\ConfiguableGridView\Helper\HelperClass;
use Bss\ConfiguableGridView\Helper\Data as CpGridHelper;
use Bss\ConfiguableGridView\Model\ResourceModel\Product\Type\Configurable as ResourceTypeConfigurable;
use Dotdigitalgroup\Email\Model\Connector\Product;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Add
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Add extends \Magento\Checkout\Controller\Cart\Add
{
    /**
     * @var ResourceTypeConfigurable
     */
    protected $resourceTypeConfigurable;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var int
     */
    protected $versionMagento = 0;

    /**
     * @var array
     */
    protected $stockData = [];

    /**
     * @var array
     */
    protected $childProductQty = [];

    /**
     * @var array
     */
    protected $childProductName = [];

    /**
     * @var HelperClass
     */
    protected $helperClass;

    /**
     * @var CpGridHelper
     */
    protected $cpGridHelper;

    /**
     * Add constructor.
     * @param ResourceTypeConfigurable $resourceTypeConfigurable
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param Session $checkoutSession
     * @param StoreManagerInterface $storeManager
     * @param Validator $formKeyValidator
     * @param CustomerCart $cart
     * @param ProductRepositoryInterface $productRepository
     * @param JsonFactory $resultJsonFactory
     * @param HelperClass $helperClass
     * @param CpGridHelper $cpGridHelper
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ResourceTypeConfigurable $resourceTypeConfigurable,
        Context $context,
        ScopeConfigInterface $scopeConfig,
        Session $checkoutSession,
        StoreManagerInterface $storeManager,
        Validator $formKeyValidator,
        CustomerCart $cart,
        ProductRepositoryInterface $productRepository,
        JsonFactory $resultJsonFactory,
        HelperClass $helperClass,
        CpGridHelper $cpGridHelper
    ) {
        $this->resourceTypeConfigurable = $resourceTypeConfigurable;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->helperClass = $helperClass;
        $this->cpGridHelper = $cpGridHelper;
        parent::__construct(
            $context,
            $scopeConfig,
            $checkoutSession,
            $storeManager,
            $formKeyValidator,
            $cart,
            $productRepository
        );
    }

    /**
     * Add to cart
     *
     * @param null/string $coreRoute
     * @return Add|Redirect
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        if (isset($params["disable_grid_table_view"]) && $params["disable_grid_table_view"] == 1) {
            return parent::execute();
        }
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        if (isset($params['configurable_grid_table']) && $params['configurable_grid_table'] == 'Yes') {
            try {
                return $this->addChildProduct($params);
            } catch (LocalizedException $e) {
                $this->addErrorMessage($e);
                $url = $this->getRedirectUrl();
                return $this->goBack($url);
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage(
                    $e,
                    __('We can\'t add this item to your shopping cart right now.')
                );
                $this->helperClass->returnLoggerInterface()->critical($e);
                return $this->goBack();
            }
        } else {
            return parent::execute();
        }
    }

    /**
     * Add child Product
     *
     * @param array $params
     * @return Add|Redirect
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function addChildProduct($params)
    {
        // check version magento 2
        $this->checkVersionMagento();
        $this->getDetailStockChildProduct($params['config_table_product_id']);
        $related = $this->getRequest()->getParam('related_product');
        $storeId = $this->_storeManager->getStore()->getId();
        $count = 0;
        $options = [];
        $productParens = $this->loadProductFactory($storeId, $params);
        $this->getQtyandNameProduct($productParens);

        foreach ($params['config_table_qty'] as $id => $qty) {
            if (isset($qty) && $qty != '' && $qty > 0) {
                $product = $this->loadProductFactory($storeId, $params);
                if (!$product) {
                    return $this->goBack();
                }
                $data = [];
                $filter = $this->helperClass->returnLocalizedToNormalized()->setOptions(
                    ['locale' => $this->helperClass->returnResolverInterface()->getLocale()]
                );
                $data['qty'] = $filter->filter($qty);
                $data['product'] = $params['product'];
                $data['super_attribute'] = $params['bss_super_attribute'][$id];

                /**
                 * If Pre Order is enabled
                 * Force check qty if product status is pre order when out of stock
                 */
                if (!$this->cpGridHelper->isEnableCompatiblePreOrderPackage()) {
                    $productId = $params['config_table_product_id'][$id];
                    if ($this->resourceTypeConfigurable->isTableExistsOrNot("inventory_stock_sales_channel") &&
                        !$this->hasStockChild($this->childProductQty[$productId], $productId, $qty)) {
                        $this->messageManager->addErrorMessage(
                            __('We don\'t have as many "%1" as you requested, ', $this->childProductName[$productId])
                        );
                        return $this->goBack();
                    }
                }
                $this->addProduct($product, $params, $data, $options);

                $options = $this->getOption($product, $options);

                $count++;
            }
        }

        $this->addRelatedProducts($related);

        if ($count == 0) {
            $this->messageManager->addError(__('No items add to your shopping cart.'));
            return $this->goBack();
        } else {
            $this->cart->save()->getQuote()->collectTotals();
            $this->_eventManager->dispatch(
                'checkout_cart_add_product_complete',
                ['product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse()]
            );
            $this->addSuccessMessage($product);
            return $this->goBack(null, $product);
        }
    }

    /**
     * Check Version Magento
     *
     * @return $this
     */
    protected function checkVersionMagento()
    {
        $version = $this->helperClass->returnProductMetadata()->getVerSion();
        if (version_compare($version, '2.3.0') >= 0) {
            $this->versionMagento = 1;
        }
        return $this;
    }

    /**
     * Get Detatil Stock Child Product
     *
     * @param array $childIds
     * @return $this
     */
    protected function getDetailStockChildProduct($childIds)
    {
        $criteria = $this->helperClass->returnStockItemCriteriaFactory()->create();
        $criteria->setProductsFilter($childIds);
        $collection = $this->helperClass->returnStockItemRepository()->getList($criteria);
        foreach ($collection->getItems() as $item) {
            $productId = $item->getProductId();
            $stockData[$productId] = [
                'manage_stock' => (bool)$item->getManageStock(),
                'backorders' => (bool)$item->getBackorders(),
                'qty' => $item->getQty()
            ];
        }
        $this->stockData = $stockData;
        return $this;
    }

    /**
     * Load Product
     *
     * @param int $storeId
     * @param array $params
     * @return object $product
     */
    protected function loadProductFactory($storeId, $params)
    {
        $product = $this->helperClass->returnProductFactory()->create()->setStoreId($storeId)->load($params['product']);
        return $product;
    }

    /**
     * Get Qty and Name Product
     *
     * @param Product $product
     */
    protected function getQtyandNameProduct($product)
    {
        $configChild = $product->getTypeInstance()->getUsedProducts($product, null);
        foreach ($configChild as $simpleProduct) {
            $this->childProductQty[$simpleProduct->getId()] = $this->getQtyofChildProduct($simpleProduct);
            $this->childProductName[$simpleProduct->getId()] = $simpleProduct->getName();
        }
    }

    /**
     * Get Qty of Child Product
     *
     * @param Product $simpleProduct
     * @return mixed
     */
    protected function getQtyofChildProduct($simpleProduct)
    {
        if ($this->versionMagento != 0) {
            return $simpleProduct->getQuantityBss();
        } else {
            return $this->stockData[$simpleProduct->getId()]['qty'];
        }
    }

    /**
     * Check has stock child
     *
     * @param int $productQty
     * @param int $productId
     * @param int $boughtQty
     * @return bool
     */
    protected function hasStockChild($productQty, $productId, $boughtQty)
    {
        $stockData = $this->stockData;
        $quantity = $stockData[$productId]['qty'];
        if ($this->versionMagento != 0) {
            $quantity = $productQty;
        }
        if ($stockData[$productId]['manage_stock'] && !$stockData[$productId]['backorders'] && $boughtQty > $quantity) {
            return false;
        }
        return true;
    }

    /**
     * Add Product
     *
     * @param object $product
     * @param array $params
     * @param array $data
     * @param array $options
     * @throws LocalizedException
     */
    protected function addProduct($product, $params, $data, $options)
    {
        if (!empty($params['options'])) {
            $data['options'] = $params['options'];
        }
        if (!empty($options)) {
            $data['options'] = $options;
        }
        $this->cart->addProduct($product, $data);
    }

    /**
     * Get option
     *
     * @param object $product
     * @param array $options
     * @return mixed
     */
    protected function getOption($product, $options)
    {
        if (empty($options)) {
            $cartItem = $this->cart->getQuote()->getItemByProduct($product);
            $options = $cartItem->getBuyRequest()->getOptions();
        }
        return $options;
    }

    /**
     * Add Related Products
     *
     * @param string $related
     */
    protected function addRelatedProducts($related)
    {
        if (!empty($related)) {
            $this->cart->addProductsByIds(explode(',', $related));
        }
    }

    /**
     * Add success message
     *
     * @param object $product
     */
    protected function addSuccessMessage($product)
    {
        if (!$this->_checkoutSession->getNoCartRedirect(true)) {
            if (!$this->cart->getQuote()->getHasError()) {
                if ($this->shouldRedirectToCart()) {
                    $message = __(
                        'You added %1 to your shopping cart.',
                        $product->getName()
                    );
                    $this->messageManager->addSuccessMessage($message);
                } else {
                    $this->messageManager->addComplexSuccessMessage(
                        'addCartSuccessMessage',
                        [
                            'product_name' => $product->getName(),
                            'cart_url' => $this->getCartUrl(),
                        ]
                    );
                }
            }
        }
    }

    /**
     * Add Error message
     *
     * @param object $e
     */
    protected function addErrorMessage($e)
    {
        if ($this->_checkoutSession->getUseNotice(true)) {
            $this->messageManager->addNoticeMessage(
                $this->helperClass->returnEscaper()->escapeHtml($e->getMessage())
            );
        } else {
            $messages = array_unique(explode("\n", $e->getMessage()));
            foreach ($messages as $message) {
                $this->messageManager->addErrorMessage(
                    $this->helperClass->returnEscaper()->escapeHtml($message)
                );
            }
        }
    }

    /**
     * Redirect url
     *
     * @return string
     */
    protected function getRedirectUrl()
    {
        $url = $this->_checkoutSession->getRedirectUrl(true);
        if (!$url) {
            $cartUrl = $this->helperClass->returnHelperCart()->getCartUrl();
            $url = $this->_redirect->getRedirectUrl($cartUrl);
        }
        return $url;
    }

    /**
     * Returns cart url
     *
     * @return string
     */
    protected function getCartUrl()
    {
        return $this->_url->getUrl('checkout/cart', ['_secure' => true]);
    }

    /**
     * Is redirect should be performed after the product was added to cart.
     *
     * @return bool
     */
    protected function shouldRedirectToCart()
    {
        return $this->_scopeConfig->isSetFlag(
            'checkout/cart/redirect_to_cart',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
