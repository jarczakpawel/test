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
namespace Bss\ConfiguableGridView\Plugin;

use Bss\ConfiguableGridView\Helper\Data as CpHelper;
use Bss\ConfiguableGridView\Helper\CartUrl as CartUrlHelper;
use Magento\Catalog\Block\Product\View;

/**
 * Class AddCartUrl
 *
 * @package Bss\ConfiguableGridView\Plugin
 */
class AddCartUrl
{
    /**
     * @var CpHelper
     */
    protected $cpHelper;

    /**
     * @var CartUrlHelper
     */
    protected $cartUrlHelper;

    /**
     * AddCartUrl constructor.
     * @param CpHelper $cpHelper
     * @param CartUrlHelper $cartUrlHelper
     */
    public function __construct(
        CpHelper $cpHelper,
        CartUrlHelper $cartUrlHelper
    ) {
        $this->cpHelper = $cpHelper;
        $this->cartUrlHelper = $cartUrlHelper;
    }

    /**
     * Custom Add to Cart Url
     *
     * @param View $productViewSubject
     * @param $result
     * @param \Magento\Catalog\Model\Product $product
     * @param array $additional
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetAddToCartUrl(
        View $productViewSubject,
        $result,
        $product,
        $additional = []
    ) {
        if ($this->cartUrlHelper->isUsingCustomAddToUrl($product)) {
            return $this->cartUrlHelper->getAddUrl($product, $additional);
        }
        return $result;
    }
}
