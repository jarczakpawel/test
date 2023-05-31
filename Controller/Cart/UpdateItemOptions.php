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
use Exception;
use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote\Item;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class UpdateItemOptions
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UpdateItemOptions extends \Magento\Checkout\Controller\Cart\UpdateItemOptions
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var DataObject
     */
    protected $dataObject;

    /**
     * @var HelperClass
     */
    protected $helperClass;

    /**
     * UpdateItemOptions constructor.
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param Session $checkoutSession
     * @param JsonFactory $resultJsonFactory
     * @param StoreManagerInterface $storeManager
     * @param Validator $formKeyValidator
     * @param CustomerCart $cart
     * @param DataObject $dataObject
     * @param HelperClass $helperClass
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        Session $checkoutSession,
        JsonFactory $resultJsonFactory,
        StoreManagerInterface $storeManager,
        Validator $formKeyValidator,
        CustomerCart $cart,
        DataObject $dataObject,
        HelperClass $helperClass
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->dataObject = $dataObject;
        $this->helperClass = $helperClass;
        parent::__construct(
            $context,
            $scopeConfig,
            $checkoutSession,
            $storeManager,
            $formKeyValidator,
            $cart
        );
    }

    /**
     * Update  Item Options
     *
     * @return Redirect
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        if (isset($params["disable_grid_table_view"]) && $params["disable_grid_table_view"] == 1) {
            return parent::execute();
        }

        if (isset($params['configurable_grid_table']) && $params['configurable_grid_table'] == 'Yes') {
            if (!isset($params['options'])) {
                $params['options'] = [];
            }
            try {
                $this->addChildProduct($params);
            } catch (LocalizedException $e) {
                $this->messageAddError($e);
                $url = $this->_checkoutSession->getRedirectUrl(true);
                return $this->returnCartUrl($url);
            } catch (Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('We can\'t update the item right now.'));
                $this->helperClass->returnLoggerInterface()->critical($e);
                return $this->_goBack();
            }
            return $this->resultRedirectFactory->create()->setPath('*/*');
        } else {
            return parent::execute();
        }
    }

    /**
     * Add Child Product
     *
     * @param array $params
     * @return Redirect
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function addChildProduct($params)
    {
        $related = $this->getRequest()->getParam('related_product');
        $storeId = $this->_storeManager->getStore()->getId();
        foreach ($params['config_table_qty'] as $id => $qty) {
            if (isset($qty)) {
                $data = [];
                $filter = $this->helperClass->returnLocalizedToNormalized()->setOptions(
                    ['locale' => $this->helperClass->returnResolverInterface()->getLocale()]
                );
                $data['super_attribute'] = $params['bss_super_attribute'][$id];
                $data['options'] = $params['options'];
                $data['qty'] = $filter->filter($qty);

                if ($params['quote_item_id'][$id] != '') {
                    $this->checkQuoteItem($params, $id);
                    $item = $this->updateItem($data, $params, $id);
                } else {
                    $this->addProducts($data, $storeId, $params);
                }
            }
        }

        $this->addRelatedProducts($related);

        $this->cart->save();

        $this->_eventManager->dispatch(
            'checkout_cart_update_item_complete',
            ['item' => $item, 'request' => $this->getRequest(), 'response' => $this->getResponse()]
        );

        if (!$this->_checkoutSession->getNoCartRedirect(true)) {
            return $this->_goBack($this->_url->getUrl('checkout/cart'));
        }
    }

    /**
     * Return cart url
     *
     * @param string $url
     * @return Redirect
     */
    protected function returnCartUrl($url)
    {
        if ($url) {
            return $this->resultRedirectFactory->create()->setUrl($url);
        }
        $cartUrl = $this->helperClass->returnHelperCart()->getCartUrl();
        return $this->resultRedirectFactory->create()->setUrl($this->_redirect->getRedirectUrl($cartUrl));
    }

    /**
     * Message Add Error
     *
     * @param object $e
     */
    protected function messageAddError($e)
    {
        if ($this->_checkoutSession->getUseNotice(true)) {
            $this->messageManager->addNoticeMessage($e->getMessage());
        } else {
            $messages = array_unique(explode("\n", $e->getMessage()));
            foreach ($messages as $message) {
                $this->messageManager->addErrorMessage($message);
            }
        }
    }

    /**
     * Check Quote tem
     *
     * @param array $params
     * @param int $id
     * @throws LocalizedException
     */
    protected function checkQuoteItem($params, $id)
    {
        $quoteItem = $this->cart->getQuote()->getItemById($params['quote_item_id'][$id]);
        if (!$quoteItem) {
            throw new LocalizedException(__('We can\'t find the quote item.'));
        }
    }

    /**
     * Update Iem
     *
     * @param array $data
     * @param array $params
     * @param int $id
     * @return CustomerCart|Item|string
     * @throws LocalizedException
     */
    protected function updateItem($data, $params, $id)
    {
        if ($data['qty'] != '' && $data['qty'] > 0) {
            $item = $this->cart->updateItem($params['quote_item_id'][$id], $this->dataObject->addData($data));
        } else {
            $item = $this->cart->removeItem($params['quote_item_id'][$id]);
        }
        if (is_string($item)) {
            throw new LocalizedException(__($item));
        }
        if ($item->getHasError()) {
            throw new LocalizedException(__($item->getMessage()));
        }
        return $item;
    }

    /**
     * Add Products
     *
     * @param array $data
     * @param int $storeId
     * @param array $params
     * @throws LocalizedException
     */
    protected function addProducts($data, $storeId, $params)
    {
        if ($data['qty'] != '' && $data['qty'] > 0) {
            $product = $this->helperClass->returnProductFactory()
                ->create()
                ->setStoreId($storeId)
                ->load($params['product']);
            if ($product) {
                $this->cart->addProduct($product, $data);
            }
        }
    }

    /**
     * Add relate Products
     *
     * @param string $related
     */
    protected function addRelatedProducts($related)
    {
        if (!empty($related)) {
            $this->cart->addProductsByIds(explode(',', $related));
        }
    }
}
