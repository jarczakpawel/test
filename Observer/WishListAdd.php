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
 * @category  BSS
 * @package   Bss_ConfigurableProductWholesale
 * @author    Extension Team
 * @copyright Copyright (c) 2017-2020 BSS Commerce Co. ( http://bsscommerce.com )
 * @license   http://bsscommerce.com/Bss-Commerce-License.txt
 */

namespace Bss\ConfiguableGridView\Observer;

use Bss\ConfiguableGridView\Helper\HelperClass;
use Bss\ConfiguableGridView\Helper\HelperWishlist;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Wishlist\Model\Wishlist;

/**
 * Class SetPriceForCart
 *
 * @package Bss\ConfigurableProductWholesale\Observer
 */
class WishListAdd implements ObserverInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var HelperClass
     */
    private $helperClass;

    /**
     * @var HelperWishlist
     */
    private $helperWishlist;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * WishListAdd constructor.
     *
     * @param RequestInterface $request
     * @param ManagerInterface $messageManager
     * @param HelperClass $helperClass
     * @param HelperWishlist $helperWishlist
     * @param LoggerInterface $logger
     */
    public function __construct(
        RequestInterface $request,
        ManagerInterface $messageManager,
        HelperClass $helperClass,
        HelperWishlist $helperWishlist,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->messageManager = $messageManager;
        $this->helperClass = $helperClass;
        $this->helperWishlist = $helperWishlist;
        $this->logger = $logger;
    }

    /**
     * Delete current wishlist item, add new wishlist item if has param['configurable_grid_table']
     *
     * @param EventObserver $observer
     * @return EventObserver|void
     */
    public function execute(EventObserver $observer)
    {
        $params = $this->request->getParams();
        if (isset($params["disable_grid_table_view"]) && $params["disable_grid_table_view"] == 1) {
            return $observer;
        }

        if (isset($params['configurable_grid_table'])
            && $params['configurable_grid_table'] == 'Yes'
            && array_sum($params['config_table_qty']) > 0
        ) {
            $wishlist = $observer->getEvent()->getWishlist();
            $item = $observer->getEvent()->getItem();
            $product = $observer->getEvent()->getProduct();
            try {
                $item->delete();
                $this->addItemToWishlist($product, $params, $wishlist);
            } catch (\Exception $e) {
                $this->logger->critical($e->getMessage());
            }
        }
    }

    /**
     * Add item to wish list
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param array $params
     * @param Wishlist $wishlist
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function addItemToWishlist($product, $params, $wishlist)
    {
        foreach ($params['config_table_qty'] as $id => $qty) {
            if (isset($qty) && $qty != '' && $qty > 0) {
                $data = [];
                $filter = $this->helperClass->returnLocalizedToNormalized()->setOptions(
                    ['locale' => $this->helperClass->returnResolverInterface()->getLocale()]
                );
                $data['qty'] = $filter->filter($qty);
                $data['product'] = $params['product'];
                $data['super_attribute'] = $params['bss_super_attribute'][$id];
                if (!empty($params['options'])) {
                    $data['options'] = $params['options'];
                }
                $buyRequest = $this->helperWishlist->returnDataObject()->addData($data);
                $wishlist->addNewItem($product, $buyRequest);
            }
        }
    }
}
