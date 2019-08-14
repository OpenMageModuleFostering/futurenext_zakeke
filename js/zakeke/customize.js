/*******************************************************
 * Copyright (C) 2017 Zakeke
 *
 * This file is part of Zakeke.
 *
 * Zakeke can not be copied and/or distributed without the express
 * permission of Zakeke
 *******************************************************/

function zakekeCustomizationAdd(config) {
    'use strict';

    var productDataCache = {},
        pendingProductDataRequests = [],
        container = window.document.getElementById('zakeke-container'),
        iframe = container.firstElementChild,
        updatedParams = function (color, zakekeOptions) {
            if (color == null) {
                throw new Error('color param is null');
            }

            var params = $jZakeke.extend({}, config.params),
                colorObj = JSON.parse(color);

            colorObj.forEach(function (val) {
                if (val.IsGlobal) {
                    if (params['super_attribute'] != null) {
                        params['super_attribute'][val.Id] = val.Value.Id;
                    }
                } else {
                    if (params['options'] != null) {
                        params['options'][val.Id] = val.Value.Id;
                    }
                }
            });

            if (zakekeOptions != null) {
                Object.keys(zakekeOptions).forEach(function(key) {
                    params[key] = zakekeOptions[key];
                });
            }

            return params;
        },
        emitProductDataEvent = function (productData) {
            iframe.contentWindow.postMessage({
                data: productData,
                zakekeMessageType: 1
            }, '*');
        },
        productData = function(color, zakekeOptions) {
            var queryString = $jZakeke.param(updatedParams(color, zakekeOptions)),
                cached = productDataCache[queryString];

            if (cached !== undefined) {
                emitProductDataEvent(cached);
                return;
            }

            if (pendingProductDataRequests.indexOf(queryString) !== -1) {
                return;
            }

            pendingProductDataRequests.push(queryString);
            $jZakeke.ajax(config.baseUrl + 'ProductDesigner/customize/price?' + queryString)
                .done(function (result) {
                    var productData = $jZakeke.extend({}, result);
                    productData.color = color;
                    productData.isOutOfStock = false;
                    productDataCache[queryString] = productData;
                    emitProductDataEvent(productData);
                })
                .fail(function () {
                    var productData = {
                        color: color,
                        isOutOfStock: true
                    };
                    productDataCache[queryString] = productData;
                    emitProductDataEvent(productData);
                })
                .always(function () {
                    var index = pendingProductDataRequests.indexOf(queryString);
                    if (index !== -1) {
                        pendingProductDataRequests.splice(index, 1);
                    }
                });
        },
        addToCart = function (color, design, model) {
            var zakekeOptions = {};
            zakekeOptions['zakeke-design'] = design;
            zakekeOptions['zakeke-model'] = model;
            zakekeOptions['form_key'] = config.formKey;
            zakekeOptions['zakeke-token'] = config.formKey;
            var params = updatedParams(color, zakekeOptions),
                form = window.document.getElementById('zakeke-addtocart');

            form.method = 'POST';
            form.action = config.checkoutUrl;
            Object.keys(params).forEach(function (key) {
                if (params[key] instanceof String || typeof(params[key]) !== 'object') {
                    var input = window.document.createElement('INPUT');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = params[key];
                    form.appendChild(input);
                } else {
                    Object.keys(params[key]).forEach(function (subKey) {
                        var input = window.document.createElement('INPUT');
                        input.type = 'hidden';
                        input.name = key + '[' + subKey + ']';
                        input.value = params[key][subKey];
                        form.appendChild(input);
                    });
                }
            });
            $(form).submit();
        };

    window.addEventListener('message', function (event) {
        if (event.origin !== config.zakekeUrl) {
            return;
        }

        if (event.data.zakekeMessageType === 0) {
            addToCart(event.data.colorId, event.data.designId, event.data.modelId);
        } else if (event.data.zakekeMessageType === 1) {
            var zakekeOptions = {};
            if (event.data.design.price !== undefined) {
                zakekeOptions['zakeke-price'] = event.data.design.price;
            }
            if (event.data.design.percentPrice !== undefined) {
                zakekeOptions['zakeke-percent-price'] = event.data.design.percentPrice;
            }
            productData(event.data.design.color, zakekeOptions);
        }
    }, false);

    if (window.matchMedia('(min-width: 768px)').matches) {
        iframe.src = config.customizerLargeUrl;
        iframe.scrollIntoView({block: 'start', behavior: 'smooth'});
    } else {
        iframe.src = config.customizerSmallUrl;
        window.addEventListener('resize', function () {
            iframe.style.minHeight = window.innerHeight + 'px';
            document.body.style.overflow = 'hidden';
        });
    }
}

