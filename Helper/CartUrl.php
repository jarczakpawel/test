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
namespace Bss\ConfiguableGridView\Helper;

use Bss\ConfiguableGridView\Helper\Data as CpHelper;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class CartUrl extends AbstractHelper
{
    /**
     * @var Data
     */
    protected $cpHelper;

    /**
     * CartUrl constructor.
     * @param Context $context
     * @param Data $cpHelper
     */
    public function __construct(
        Context $context,
        CpHelper $cpHelper
    ) {
        parent::__construct($context);
        $this->cpHelper = $cpHelper;
    }

    /**
     * Use custom add to cart url or not
     *
     * @param Product $product
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function isUsingCustomAddToUrl($product)
    {
        $productType = $product->getTypeId();
        $attrDisableCpGrid = $product->getData('disable_grid_table_view');
        if ($this->cpHelper->isEnabled() &&
            $productType == 'configurable' &&
            !$attrDisableCpGrid) {
            return true;
        }
        return false;
    }

    /**
     * Retrieve url for add product to cart
     *
     * @param Product $product
     * @param array $additional
     * @return string
     */
    public function getAddUrl($product, $additional = [])
    {
        if (isset($additional['useUencPlaceholder'])) {
            $uenc = "%uenc%";
            unset($additional['useUencPlaceholder']);
        } else {
            $uenc = $this->urlEncoder->encode($this->_urlBuilder->getCurrentUrl());
        }

        $urlParamName = ActionInterface::PARAM_NAME_URL_ENCODED;

        $routeParams = [
            $urlParamName => $uenc,
            'product' => $product->getEntityId(),
            '_secure' => $this->_getRequest()->isSecure()
        ];

        if (!empty($additional)) {
            $routeParams = array_merge($routeParams, $additional);
        }

        if ($product->hasUrlDataObject()) {
            $routeParams['_scope'] = $product->getUrlDataObject()->getStoreId();
            $routeParams['_scope_to_url'] = true;
        }

        if ($this->_getRequest()->getRouteName() == 'checkout'
            && $this->_getRequest()->getControllerName() == 'cart'
        ) {
            $routeParams['in_cart'] = 1;
        }

        return $this->_getUrl('configurableGrid/cart/add', $routeParams);
    }
}
