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
define(
    [
        'jquery',
        'mage/url',
        'mage/translate',
        'mage/template',
        'underscore',
        'Bss_ConfiguableGridView/js/table'
    ],
    function ($,urlBuilder, $t, mageTemplate, _) {
        'use strict';
        return function (config) {
            window.configuable_qty_price_array = config.configuable_qty_price_array;
            window.old_configuable_qty_price_array = config.configuable_qty_price_array;
            window.bss_optionAmount = '';
            window.bss_configurablegridview_timer = '';
            var jsonSwatchConfig = config.jsonSwatchConfig,
                jsonAttrLabelConfig = config.jsonAttrLabelConfig,
                isDisplayBothPrices = (parseInt(config.isDisplayBothPrices) == true);
            var messageTierPrice = config.messageTierPrice,
                dataProduct = config.dataProduct,
                configATPrice = config.configATPrice,
                configTableTP = config.configTableTP,
                urlAjax = config.urlAjax;

            $.ajax({
                url: urlAjax,
                data: {},
                type: 'post',
                dataType: 'json',
                cache: false,
                success: function (res) {
                    if (res && $('.checkout-cart-configure').length > 0) {
                        $('#bss_configurablegridview .qty_att_product').each(function () {
                            var productId = parseFloat($(this).attr('productid')),
                                selector = $(this);
                            $.each(res, function(i,item){
                                if (i == productId) {
                                    selector.attr('value', item.qty);
                                    selector.closest('.item-info').find('.quote_item_id').val(item.item_id);
                                    selector.trigger('change');
                                }
                            })
                        })
                    }
                },
            });

            $(document).ready(
                function () {
                    $('#bss_configurablegridview .qty_att_product').on("click", function () {
                        if (configTableTP == 1) {
                            $(".price-box.price-tier_price").addClass("hide");
                        }
                    })
                    if ($('#bss-price-range').length > 0) {
                        if ($('.product-info-price>.price-box').length) {
                            $('.product-info-price>.price-box').html($('#bss-price-range').html()).css('width', 'auto');
                        }

                    }
                    $(".tier-table-price").tableHeadFixer();
                    $(".configurable-product-table").tableHeadFixer();
                    var configuable_array_price = [];
                    var configuableArrayPriceExclTax = [];
                    var configuable_price_old = config.configuable_price_old;
                    var old_subtotal = 0;
                    var configuable_currency_symbol = config.configuable_currency_symbol;
                    var configuable_currency_symbol_position = configuable_price_old.indexOf(configuable_currency_symbol);
                    var showTotalDetailed = config.showTotalDetailed;

                    if (jQuery('.price-box .special-price:first').length > 0) {
                        var isSpecial = true;
                        var configuable_array_price_old = [];
                        var configuable_price_regular_old = config.configuable_price_regular_old;
                    } else {
                        var isSpecial = false;
                    }

                    if (showTotalDetailed) {
                        $('#product-options-wrapper').append($('#bss-total-check-show').html());
                        if ($('#product-options-wrapper').length === 0) {
                            $('#bss-total-check-show').show();
                        }
                    }

                    /**
                     * Format price and add currency
                     * @param price float
                     * @returns string
                     */
                    function convertPriceConfiguableProduct(price) {
                        price = parseFloat(price).toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, "$1,");
                        if (configuable_currency_symbol_position > 0) {
                            return price += configuable_currency_symbol;
                        }

                        return configuable_currency_symbol + price;
                    }

                    /**
                     * Wrap template of price exclude tax
                     * @param priceExclTax float
                     * @returns html
                     */
                    function priceExclTaxTemplate(priceExclTax) {
                        return mageTemplate('#bss-configurable-price-excl-tax',
                            {
                                data: {
                                    title: $t('Excl. Tax: '),
                                    price: priceExclTax
                                }
                            }
                        );
                    }

                    function notATPrice(indexField, buyQty, productId) {
                        var price = dataProduct[indexField]["price"]["finalPrice"],
                            excTaxPrice = dataProduct[indexField]["price"]["excl_tax"],
                            buyPrice = buyQty * price,
                            buyPriceExclTax = buyQty * dataProduct[indexField]["price"]["excl_tax"];
                        if (typeof dataProduct[indexField].tier_price !== 'undefined') {
                            dataProduct[indexField]["tier_price"].forEach(
                                function (tierPrice, indexTierPrice) {
                                    if (buyQty >= tierPrice["qty"]) {
                                        if (excTaxPrice >= tierPrice["price_excl_tax"]) {
                                            excTaxPrice = tierPrice["price_excl_tax"];
                                            buyPrice = buyQty * tierPrice["price"];
                                            buyPriceExclTax = buyQty * tierPrice["price_excl_tax"];
                                            price = tierPrice["price"];
                                        }
                                    }

                                }
                            )
                        }
                        dataProduct[indexField]["buy_price_excl_tax"] = buyPriceExclTax;
                        dataProduct[indexField]["buy_price"] = buyPrice;
                        changeHtml(productId, buyQty, price, excTaxPrice, buyPrice);
                    }

                    /**
                     * Change HTMl unit price,Subtotal,total
                     */
                    function changeHtml(productId, buyQty, unitPrice, excTaxPrice, buyPrice) {
                        unitPrice = convertPriceConfiguableProduct(unitPrice);
                        var sysbolExcTaxPrice = convertPriceConfiguableProduct(excTaxPrice);

                        var htmlUnitPrice = '<span class="price">' + unitPrice + '</span>',
                            htmlExcTaxPrice = '<span class="price">' + sysbolExcTaxPrice + '</span>',
                            final_price = convertPriceConfiguableProduct(buyPrice),
                            buyExcTaxPrice = '<small>' + 'Excl. Tax:' + convertPriceConfiguableProduct(buyQty * excTaxPrice) + '<small>';
                        $("#unit-price-" + productId + " #product-price-" + productId).html(htmlUnitPrice);
                        $("#unit-price-" + productId + " #price-including-tax-product-price-" + productId).html(htmlUnitPrice);
                        $("#unit-price-" + productId + " #price-excluding-tax-product-price-" + productId).html(htmlExcTaxPrice);
                        $("#final-price-" + productId).html(final_price);
                        if (isDisplayBothPrices) {
                            $('#excl-tax-' + productId).html(buyExcTaxPrice);
                        }
                    }

                    /**
                     * Search Index product
                     */
                    function indexProduct(productId) {
                        var indexProduct = 0;
                        dataProduct.forEach(function (item, index) {
                            if (item["product_id"] == productId) {
                                indexProduct = index;
                            }
                        })
                        return indexProduct;
                    }

                    /**
                     * Qty input box on change handler
                     */
                    $('#bss_configurablegridview .qty_att_product').change(
                        function () {
                            var confi_qty_value = $(this).val();
                            if(confi_qty_value == "") {
                                confi_qty_value = 0;
                            } else {
                                confi_qty_value = parseFloat(confi_qty_value);
                            }
                            if (Number.isNaN(confi_qty_value)) {
                                return;
                            }
                            var productId = $(this).attr("productId");
                            var indexField = $(this).attr("index");

                            var buyQty = confi_qty_value,
                                price = dataProduct[indexField]["price"]["finalPrice"],
                                excTaxPrice = dataProduct[indexField]["price"]["excl_tax"],
                                buyPrice = buyQty * price,
                                buyPriceExclTax = buyQty * dataProduct[indexField]["price"]["excl_tax"];
                            notATPrice(indexField, confi_qty_value, productId);
                            if (configATPrice == 1) {
                                var firstProductId = dataProduct[0]["product_id"];
                                dataProduct.forEach(
                                    function (item, index, dataProduct) {
                                        if (index == indexField) {
                                            if (typeof item.advanced_tier_price !== 'undefined') {
                                                var idFirstProductAdvanced = dataProduct[index]["advanced_tier_price"]["product_ids"][0],
                                                    indexFirstProductAdvanced = indexProduct(idFirstProductAdvanced);
                                                dataProduct[indexFirstProductAdvanced]["qty_advanced"] += confi_qty_value - item["buy_qty"];
                                                dataProduct[index]["buy_qty"] = confi_qty_value;
                                                var currentQtyAdvanced = dataProduct[indexFirstProductAdvanced]["qty_advanced"];

                                                var aTPrice = item["advancecd_tier_price"];
                                                var tierPrices = item['tier_price'];
                                                var priceExclTax = 0;
                                                tierPrices.forEach(function (tierPrice, indexTP, tierPrices) {
                                                    if (currentQtyAdvanced >= tierPrice["qty"]) {
                                                        dataProduct[indexFirstProductAdvanced]["advanced"] = 1;
                                                        var product_ids = item["advanced_tier_price"]["product_ids"];
                                                        var price = tierPrice['price'];
                                                        var unitPrice = tierPrice['price'],
                                                            excTaxPrice = tierPrice['price_excl_tax'];
                                                        var htmlUnitPrice = '<span class="price">' + unitPrice + '</span>',
                                                            htmlExcTaxPrice = '<span class="price">' + excTaxPrice + '</span>';
                                                        product_ids.forEach(
                                                            function (productId, productIndex, product_ids) {
                                                                dataProduct[productIndex]["advanced"] = 1
                                                                var index = indexProduct(productId);
                                                                var buyQty = dataProduct[index]["buy_qty"],
                                                                    buyPrice = buyQty * dataProduct[index]["tier_price"][indexTP]["price"],
                                                                    buyPriceExclTax = buyQty * dataProduct[index]["tier_price"][indexTP]["price_excl_tax"];
                                                                if (priceExclTax >= tierPrice['price_excl_tax'] || priceExclTax == 0) {
                                                                    dataProduct[index]["buy_price"] = buyPrice;
                                                                    dataProduct[index]["buy_price_excl_tax"] = buyPriceExclTax;
                                                                    changeHtml(productId, buyQty, price, excTaxPrice, buyPrice);
                                                                    priceExclTax = tierPrice['price_excl_tax'];
                                                                }
                                                            }
                                                        )
                                                    }
                                                })
                                                if (item["advanced"] > 0 && currentQtyAdvanced < item["qty"]) {
                                                    dataProduct[indexFirstProductAdvanced]["advanced"] = 0;
                                                    var product_ids = item["advanced_tier_price"]["product_ids"];
                                                    var price = item['advanced_tier_price']['price'];
                                                    product_ids.forEach(
                                                        function (productId, productIndex, product_ids) {
                                                            var index = indexProduct(productId),
                                                                buyQty = dataProduct[index]["buy_qty"];
                                                            notATPrice(index, buyQty, productId);
                                                        }
                                                    )
                                                }
                                            }
                                        }
                                    }
                                );
                            }
                            dataProduct[indexField]["buy_qty"] = confi_qty_value;
                            var sumPrice = 0;
                            dataProduct.forEach(
                                function (item, index) {
                                    sumPrice += item["buy_price"];
                                }
                            )
                            var trElement = $(this).parent().parent();
                            var index = $(this).attr('index');

                            if (showTotalDetailed) {
                                var totalDetail = [];
                            }
                            var sumTotalExclTax = 0;
                            dataProduct.forEach(
                                function (item, index) {
                                    sumTotalExclTax += item["buy_price_excl_tax"];
                                }
                            )
                            sumTotalExclTax = convertPriceConfiguableProduct(sumTotalExclTax);
                            if (showTotalDetailed) {
                                $('#bss_configurablegridview .qty_att_product').each(
                                    function () {
                                        var eachQty = parseFloat($(this).val());

                                        var firstAttr = $(this).parent().parent().find('.first-attr');
                                        var firstId = firstAttr.attr('attribute-id');
                                        var firstValue = firstAttr.attr('attribute-value');

                                        if (eachQty > 0) {
                                            if (typeof totalDetail[firstId + firstValue] == 'undefined') {
                                                totalDetail[firstId + firstValue] = [];
                                                totalDetail[firstId + firstValue]['id'] = firstValue;
                                                totalDetail[firstId + firstValue]['qty'] = parseFloat(eachQty);
                                                if (jsonSwatchConfig && jsonSwatchConfig[firstId] && jsonSwatchConfig[firstId][firstValue]) {
                                                    totalDetail[firstId + firstValue]['attr'] = jsonSwatchConfig[firstId][firstValue];
                                                }
                                                totalDetail[firstId + firstValue]['label'] = jsonAttrLabelConfig[firstId][firstValue];
                                            } else {
                                                totalDetail[firstId + firstValue]['qty'] += parseFloat(eachQty);
                                            }
                                        }
                                    }
                                );

                                var rows = '';
                                var totalQty = 0;
                                var type = '', value = '', thumb = '', label = '', attr = '';
                                for (var key in totalDetail) {
                                    if (parseFloat(totalDetail[key]['qty']) > 0) {
                                        var id = totalDetail[key]['id'];
                                        var html = '';
                                        if (totalDetail[key]['attr']) {
                                            type = parseFloat(totalDetail[key]['attr'].type, 10);
                                            value = totalDetail[key]['attr'].hasOwnProperty('value') ? totalDetail[key]['attr'].value : '';
                                            thumb = totalDetail[key]['attr'].hasOwnProperty('thumb') ? totalDetail[key]['attr'].thumb : '';
                                            label = totalDetail[key]['attr'].hasOwnProperty('label') ? totalDetail[key]['attr'].label : '';
                                            attr =
                                                ' option-type="' + type + '"' +
                                                ' option-id="' + id + '"' +
                                                ' option-label="' + label + '"' +
                                                ' option-tooltip-thumb="' + thumb + '"' +
                                                ' option-tooltip-value="' + value + '"';

                                            if (type === 0) {
                                                // Text
                                                html += '<div class="swatch-option text" ' + attr + '>' + (value ? value : label) +
                                                    '</div>';
                                            } else if (type === 1) {
                                                // Color
                                                html += '<div class="swatch-option color" ' + attr +
                                                    '" style="background: ' + value +
                                                    ' no-repeat center; background-size: initial;">' + '' +
                                                    '</div>';
                                            } else if (type === 2) {
                                                // Image
                                                html += '<div class="swatch-option image" ' + attr +
                                                    '" style="background: url(' + value + ') no-repeat center; background-size: initial;">' + '' +
                                                    '</div>';
                                            } else if (type === 3) {
                                                // Clear
                                                html += '<div' + attr + '></div>';
                                            } else {
                                                // Defaualt
                                                html += '<div' + attr + '>' + label + '</div>';
                                            }
                                        } else {
                                            html += '<div>' + totalDetail[key]['label']['label'] + '</div>';
                                        }

                                        rows += '<tr><td>' + html + '</td><td class="a-right">' + totalDetail[key]['qty'] + '</td></tr>';
                                        totalQty += parseFloat(totalDetail[key]['qty']);
                                    }
                                }

                                if (rows != '') {
                                    var table = '<div class="content-detail-qty"><table id="qty-selected-detail-table">' + rows + '</table></div>';
                                    jQuery('.total-area .qty-detail').html(table);
                                } else {
                                    jQuery('.total-area .qty-detail').html("");
                                }

                                $('.total-area .qty-total .value').text(totalQty);
                                $('.total-area .price-total .value').text(convertPriceConfiguableProduct(sumPrice));
                                if (isDisplayBothPrices) {
                                    $('.total-area .price-total .total-excl-tax').html(priceExclTaxTemplate(sumTotalExclTax));
                                }
                            }
                        }
                    );

                    $('#bss_configurablegridview tbody tr.item-info').click(
                        function () {
                            $(this).find('.bss-swatch').each(
                                function () {
                                    var attributeId = $(this).attr('attribute-id'),
                                        attributeValue = $(this).attr('attribute-value');
                                    $('#attribute' + attributeId).val(attributeValue).change();
                                }
                            );
                        }
                    );

                    /**
                     * Reset button click handle
                     */
                    $('.reset-configurablegridview').click(
                        function () {
                            if (bss_optionAmount !== '') {
                                var new_configuable_price_old = parseFloat(configuable_price_old) + parseFloat(bss_optionAmount);
                            } else {
                                var new_configuable_price_old = configuable_price_old;
                            }


                            configuable_array_price = [];
                            configuable_array_price_old = [];
                            $('#bss_configurablegridview .qty_att_product').each(
                                function () {
                                    $(this).val(0);
                                    if ($(this).parent().parent().find('.subtotal').length > 0) {
                                        $(this).parent().parent().find('.subtotal .final-price').text(convertPriceConfiguableProduct(old_subtotal));
                                    }
                                }
                            );
                            $('.total-area .qty-detail').html("");
                            $('.total-area .qty-total .value').text(0);
                            $('.total-area .price-total .value').text(convertPriceConfiguableProduct(0));
                            if (isDisplayBothPrices) {
                                $('.total-area .price-total .total-excl-tax').html(priceExclTaxTemplate(convertPriceConfiguableProduct(0)));
                            }
                            dataProduct.forEach(function (item, index, dataProduct) {
                                    dataProduct[index]["buy_qty"] = 0;
                                    dataProduct[index]["buy_price"] = 0;
                                    dataProduct[index]["buy_price_excl_tax"] = 0;
                                    dataProduct[index]["qty_advanced"] = 0;
                                    $("#unit-price-" + item["product_id"]).html(item["html_unit_price"]);
                                }
                            );
                        }
                    );
                    $("#bss_configurablegridview tbody tr.item-info").hover(
                        function () {
                            var productId = $(this).attr("product_id");
                            var indexTr = $(this).attr("index");
                            var message = "";
                            for (var index in messageTierPrice) {
                                if (index == indexTr && messageTierPrice[index] != -1) {
                                    messageTierPrice[index].forEach(
                                        function (item, index) {
                                            $("#bss-tier-detailed-" + productId).removeClass("hide");
                                            if (index == 0) {
                                                $("#tier-price-" + productId).html('<li class="item">' + item + '</li>');
                                            } else {
                                                $("#tier-price-" + productId).append('<li class="item">' + item + '</li>');
                                            }
                                        }
                                    );
                                    break;
                                }
                            }
                        }
                    )
                    $("#bss_configurablegridview tbody tr.item-info").mouseleave(
                        function () {
                            $(".bss-tier-detailed").addClass("hide");
                        }
                    )
                }
            );
        }
    }
);
