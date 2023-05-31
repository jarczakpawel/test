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
        'underscore',
        'mage/template',
        'Magento_Catalog/js/price-utils',
        'jquery/ui',
        'mage/translate',
        'Magento_Swatches/js/swatch-renderer'

    ],
    function ($, _, mageTemplate, priceUtils) {
        "use strict";

        $.widget('bss.swatch',
            $.mage.SwatchRenderer, {
                options: {
                    classes: {
                        attributeClass: 'swatch-attribute',
                        attributeLabelClass: 'swatch-attribute-label',
                        attributeSelectedOptionLabelClass: 'swatch-attribute-selected-option',
                        attributeOptionsWrapper: 'swatch-attribute-options',
                        attributeInput: 'swatch-input',
                        optionClass: 'swatch-option',
                        selectClass: 'swatch-select',
                        moreButton: 'swatch-more',
                        loader: 'swatch-option-loading'
                    },
                    // option's json config
                    jsonConfig: {},

                    // Use ajax to get image data
                    useAjax: false,

                    // swatch's json config
                    jsonSwatchConfig: {},

                    mediaGallerySelector: '[data-gallery-role=gallery-placeholder]',
                    mediaCallback: '',
                    mediaCache: {},
                    mediaGalleryInitial: [{}],
                    onlyMainImg: false,
                    magentoVersion: '',
                },

                _create: function () {
                    var opt = this.options,
                        $widget = this;
                    $widget.element.on('click',
                        function () {
                            $('.bss-swatch .swatch-option').removeClass('selected');
                            $(this).find('.swatch-option').addClass('selected');
                            return $widget._LoadProductMedia($(this));
                        }
                    );
                },

                _init: function () {
                    if (_.isEmpty(this.options.jsonConfig.images)) {
                        this.options.useAjax = true;
                        // creates debounced variant of _LoadProductMedia()
                        // to use it in events handlers instead of _LoadProductMedia()
                        this._debouncedLoadProductMedia = _.debounce(this._LoadProductMedia.bind(this), 500);
                    }

                    if (this.options.jsonConfig !== '' && this.options.jsonSwatchConfig !== '') {
                        this._RenderControls();
                    } else {
                        console.log('SwatchRenderer: No input data received');
                    }
                },

                _RenderControls: function () {
                    var $widget = this,
                        container = this.element,
                        classes = this.options.classes,
                        attr_id = '';

                    $widget.optionsMap = {};
                    $.each(this.options.jsonConfig.attributes,
                        function () {
                            var item = this,
                                options = $widget._RenderSwatchOptions(item),
                                label = '';

                            // Show only swatch controls
                            if ($widget.options.onlySwatches && !$widget.options.jsonSwatchConfig.hasOwnProperty(item.id)) {
                                return;
                            }

                            if ($widget.productForm) {
                                $widget.productForm.append(input);
                                input = '';
                            }

                            // Create new control
                            attr_id = container.find('input.swatch-attribute').val();
                            if (options[attr_id] == undefined) {
                                return;
                            }
                            container.find('.attr-label').remove();
                            container.append(
                                '<div class="' + classes.attributeClass + ' ' + item.code +
                                '" attribute-code="' + item.code +
                                '" attribute-id="' + item.id + '">' +
                                label +
                                '<div class="' + classes.attributeOptionsWrapper + ' clearfix">' +
                                options[attr_id] +
                                '</div>' +
                                '</div>'
                            );

                            $widget.optionsMap[item.id] = {};

                            // Aggregate options array to hash (key => value)
                            $.each(item.options,
                                function () {
                                    if (this.products.length > 0) {
                                        $widget.optionsMap[item.id][this.id] = {
                                            price: parseInt(
                                                $widget.options.jsonConfig.optionPrices[this.products[0]].finalPrice.amount,
                                                10
                                            ),
                                            products: this.products
                                        };
                                    }
                                }
                            );
                        }
                    );
                },

                _RenderSwatchOptions: function (config) {
                    var optionConfig = this.options.jsonSwatchConfig[config.id],
                        optionClass = this.options.classes.optionClass,
                        moreLimit = parseInt(this.options.numberToShow, 10),
                        moreClass = this.options.classes.moreButton,
                        moreText = this.options.moreButtonText,
                        countAttributes = 0,
                        obj = {};

                    if (!this.options.jsonSwatchConfig.hasOwnProperty(config.id)) {
                        return '';
                    }

                    $.each(config.options,
                        function () {
                            var id,
                                type,
                                value,
                                thumb,
                                label,
                                html = '',
                                attr;
                            if (!optionConfig.hasOwnProperty(this.id)) {
                                return '';
                            }

                            // Add more button
                            if (moreLimit === countAttributes++) {
                                html += '<a href="#" class="' + moreClass + '">' + moreText + '</a>';
                            }

                            id = this.id;
                            type = parseInt(optionConfig[id].type, 10);
                            value = optionConfig[id].hasOwnProperty('value') ? optionConfig[id].value : '';
                            thumb = optionConfig[id].hasOwnProperty('thumb') ? optionConfig[id].thumb : '';
                            label = this.label ? this.label : '';
                            attr =
                                ' option-type="' + type + '"' +
                                ' option-id="' + id + '"' +
                                ' option-label="' + label + '"' +
                                ' option-tooltip-thumb="' + thumb + '"' +
                                ' option-tooltip-value="' + value + '"';

                            if (!this.hasOwnProperty('products') || this.products.length <= 0) {
                                attr += ' option-empty="true"';
                            }

                            if (type === 0) {
                                // Text
                                html += '<div class="' + optionClass + ' text" ' + attr + '>' + (value ? value : label) +
                                    '</div>';
                            } else if (type === 1) {
                                // Color
                                html += '<div class="' + optionClass + ' color" ' + attr +
                                    '" style="background: ' + value +
                                    ' no-repeat center; background-size: initial;">' + '' +
                                    '</div>';
                            } else if (type === 2) {
                                // Image
                                html += '<div class="' + optionClass + ' image" ' + attr +
                                    '" style="background: url(' + value + ') no-repeat center; background-size: initial;">' + '' +
                                    '</div>';
                            } else if (type === 3) {
                                // Clear
                                html += '<div class="' + optionClass + '" ' + attr + '></div>';
                            } else {
                                // Defaualt
                                html += '<div class="' + optionClass + '" ' + attr + '>' + label + '</div>';
                            }
                            obj[id] = html;
                        });

                    return obj;
                },


                _LoadProductMedia: function ($this) {
                    var $widget = this,
                        attributes = {},
                        productId = 0,
                        mediaCallData,
                        mediaCacheKey,
                        isProductViewExist = $('body.catalog-product-view').size() > 0,
                        $main = isProductViewExist ? $this.parents('.column.main') : $this.parents('.product-item-info'),
                        mediaSuccessCallback = function (data) {
                            if (!(mediaCacheKey in $widget.options.mediaCache)) {
                                $widget.options.mediaCache[mediaCacheKey] = data;
                            }
                            $widget._ProductMediaCallback($this, $main, data, isProductViewExist);
                        },
                        images;

                    // attributes['color'] = $this.find('input').val();
                    attributes[$this.attr('attribute-code')] = $this.find('input').val();
                    productId = $this.parents('tr').data('product-id');

                    mediaCallData = {
                        'product_id': productId,
                        'attributes': attributes
                        // 'additional': $.parseQuery()
                    };
                    mediaCacheKey = JSON.stringify(mediaCallData);

                    if (this.options.useAjax) {
                        if (mediaCacheKey in $widget.options.mediaCache) {
                            mediaSuccessCallback($widget.options.mediaCache[mediaCacheKey]);
                        } else {
                            mediaCallData.isAjax = true;
                            $widget._XhrKiller();
                            // $widget._EnableProductMediaLoader($this);
                            $widget.xhr = $.ajax(
                                {
                                    url: $widget.options.mediaCallback,
                                    cache: true,
                                    type: 'GET',
                                    dataType: 'json',
                                    data: mediaCallData,
                                    success: mediaSuccessCallback
                                }
                            )
                                .done(function () {
                                        $widget._XhrKiller();
                                    }
                                );
                        }
                    } else {
                        images = $widget.options.jsonConfig.images[productId];

                        if (!images) {
                            images = $widget.options.mediaGalleryInitial;
                        }
                        $widget.updateBaseImage($widget._sortImages(images), $main, isProductViewExist);
                    }
                },
                /**
                 * Sorting images array
                 *
                 * @private
                 */
                _sortImages: function (images) {
                    return _.sortBy(images, function (image) {
                            return image.position;
                        }
                    );
                },
                _ProductMediaCallback: function ($this, $main, response, isProductViewExist) {
                    var $widget = this,
                        images = [],
                        support = function (e) {
                            return e.hasOwnProperty('large') && e.hasOwnProperty('medium') && e.hasOwnProperty('small');
                        };

                    if (_.size($widget) < 1 || !support(response)) {
                        this.updateBaseImage(this.options.mediaGalleryInitial, $main, isProductViewExist);

                        return;
                    }

                    images.push(
                        {
                            full: response.large,
                            img: response.large,
                            thumb: response.small,
                            isMain: true
                        }
                    );

                    if (response.hasOwnProperty('gallery')) {
                        $.each(response.gallery, function () {
                                if (!support(this) || response.large === this.large) {
                                    return;
                                }
                                images.push(
                                    {
                                        full: this.large,
                                        img: this.medium,
                                        thumb: this.small
                                    }
                                );
                            }
                        );
                    }

                    this.updateBaseImage(images, $main, isProductViewExist);
                },
                updateBaseImage: function (images, context, isProductViewExist) {
                    if (this.options.magentoVersion >= '2.1.0') {
                        var justAnImage = images[0],
                            updateImg,
                            imagesToUpdate,
                            gallery = context.find(this.options.mediaGallerySelector).data('gallery'),
                            item;

                        if (isProductViewExist) {
                            imagesToUpdate = images.length ? this._setImageType($.extend(true, [], images)) : [];

                            if (this.options.onlyMainImg === "true") {
                                updateImg = imagesToUpdate.filter(function (img) {
                                        return img.isMain;
                                    }
                                );
                                item = updateImg.length ? updateImg[0] : imagesToUpdate[0];
                                gallery.updateDataByIndex(0, item);

                                gallery.seek(1);
                            } else {
                                imagesToUpdate = this._setImageIndex(imagesToUpdate);
                                gallery.updateData(imagesToUpdate);
                                $(this.options.mediaGallerySelector).AddFotoramaVideoEvents();
                            }
                        } else if (justAnImage && justAnImage.img) {
                            context.find('.product-image-photo').attr('src', justAnImage.img);
                        }
                    } else {
                        var justAnImage = images[0];

                        if (isProductViewExist) {
                            context
                                .find('[data-gallery-role=gallery-placeholder]')
                                .data('gallery')
                                .updateData(images);
                        } else if (justAnImage && justAnImage.img) {
                            context.find('.product-image-photo').attr('src', justAnImage.img);
                        }
                    }
                },
                /**
                 * Set correct indexes for image set.
                 *
                 * @param {Array} images
                 * @private
                 */
                _setImageIndex: function (images) {
                    var length = images.length,
                        i;

                    for (i = 0; length > i; i++) {
                        images[i].i = i + 1;
                    }

                    return images;
                },
                _setImageType: function (images) {
                    var initial = this.options.mediaGalleryInitial[0].img;

                    if (images[0].img === initial) {
                        images = $.extend(true, [], this.options.mediaGalleryInitial);
                    } else {
                        images.map(function (img) {
                                img.type = 'image';
                            }
                        );
                    }

                    return images;
                },
                _XhrKiller: function () {
                    var $widget = this;

                    if ($widget.xhr !== undefined && $widget.xhr !== null) {
                        $widget.xhr.abort();
                        $widget.xhr = null;
                    }
                },

            }
        );
        return $.bss.swatch;
    }
);
