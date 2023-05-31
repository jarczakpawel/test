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
define([
    'jquery',
    'Magento_Catalog/js/price-box'
], function($){
    'use strict';
    return function (config) {
        var priceBoxes = $('[data-role=priceBox]');
        if (undefined != config.priceJsonConfig.productId) {
            priceBoxes = $('[data-role=priceBox][data-product-id="'+config.priceJsonConfig.productId+'"]');
        }

        priceBoxes = priceBoxes.filter(function(index, elem){
            return !$(elem).find('.price-from').length;
        });

        priceBoxes.priceBox({'priceConfig': config.priceJsonConfig});
    }
});
