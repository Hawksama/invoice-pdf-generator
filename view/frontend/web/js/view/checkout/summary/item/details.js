define(
    [
        'jquery',
        'Magento_Checkout/js/view/summary/item/details',
        'Magento_Checkout/js/model/quote',
        'Magento_Ui/js/modal/confirm',
        'Amasty_Checkout/js/action/remove-item',
        'Amasty_Checkout/js/action/update-item',
        'mage/translate',
        'ko',
        'Amasty_Checkout/js/options/configurable',
        'priceOptions',
        'mage/validation',
        'mage/url'
    ],
    function ($, Component, quote, confirm, removeItemAction, updateItemAction, $t, ko, configurable, priceOptions, validation, url) {
        "use strict";
        return Component.extend({
            defaults: {
                template: 'Amasty_Checkout/checkout/summary/item/details'
            },
            giftWrapProductId: window.checkoutConfig.giftWrapProductId,

            getGiftWrapProductId: function () {
                return this.giftWrapProductId;
            },

            /**
             * @param item
             * @return {*}
             */
            getItemConfig: function (item) {
                return this.getPropertyDataFromItem(item, 'amcheckout');
            },

            /**
             *
             * @param item
             * @param propertyName
             * @return {*}
             */
            getPropertyDataFromItem: function (item, propertyName) {
                var property,
                    itemDetails;

                if (item.hasOwnProperty(propertyName)) {
                    property = item[propertyName];
                }

                var quoteItem = this.getItemFromQuote(item);

                if (quoteItem.hasOwnProperty(propertyName)) {
                    property = quoteItem[propertyName];
                }

                if (property) {
                    this.storage().set('item_details' + item.item_id + propertyName, property);

                    return property;
                }

                itemDetails = this.storage().get('item_details' + item.item_id + propertyName);

                return itemDetails ? itemDetails : false;
            },

            /**
             *
             * @param item
             * @return {*}
             */
            getStockStatusHtml: function (item) {
                return this.getPropertyDataFromItem(item, 'amstockstatus');
            },
            /**
             *
             * @param item
             * @return {*}
             */
            getItemFromQuote: function (item) {
                var items = quote.getItems();
                var quoteItems = items.filter(function (quoteItem) {
                    return quoteItem.item_id == item.item_id;
                });

                if (quoteItems.length == 0) {
                    return false;
                }

                return quoteItems[0];
            },

            getConfigurableOptions: function (item) {
                var itemConfig = this.getItemConfig(item);

                if (itemConfig.hasOwnProperty('configurableAttributes')) {
                    return itemConfig.configurableAttributes.template;
                }

                return '';
            },

            getCustomOptions: function (item) {
                var itemConfig = this.getItemConfig(item);

                if (itemConfig.hasOwnProperty('customOptions')) {
                    return itemConfig.customOptions.template;
                }

                return '';
            },

            getUrl: function(item) {
                var itemConfig = this.getItemFromQuote(item);
                if(itemConfig.product && itemConfig.product.url_key) {
                    return url.build(itemConfig.product.url_key) + '.html';
                }
                return "#";
            },

            initOptions: function (item) {
                var self = this;
                var itemConfig = this.getItemConfig(item);

                var containerSelector = '[data-role="product-attributes"][data-item-id=' + item.item_id + ']';
                var container = $(containerSelector);

                if (itemConfig.hasOwnProperty('configurableAttributes')) {
                    container.amcheckoutConfigurable({
                        spConfig: JSON.parse(itemConfig.configurableAttributes.spConfig),
                        superSelector: containerSelector + ' .super-attribute-select'
                    });
                }

                if (itemConfig.hasOwnProperty('customOptions')) {
                    container.priceOptions({
                        optionConfig: JSON.parse(itemConfig.customOptions.optionConfig)
                    });
                }

                item.form = container;
                item.isUpdated = ko.observable(false);
                item.validation = container.validation();

                container.find('input, select, textarea').change(function () {
                    self.updateItem(item);
                });
            },

            updateItem: function (item) {
                if (item.validation.valid()) {
                    updateItemAction(item.item_id, item.form.serialize());
                }
            },

            deleteItem: function (item) {
                confirm({
                    content: $t("Are you sure you would like to remove this item from the shopping cart?"),
                    actions: {
                        confirm: function () {
                            removeItemAction(item.item_id);
                        },
                        always: function (event) {
                            event.stopImmediatePropagation();
                        }
                    }
                });
            },

            canShowDeleteButton: function () {
                return quote.getItems().length >= 1;
            }
        });
    }
);
