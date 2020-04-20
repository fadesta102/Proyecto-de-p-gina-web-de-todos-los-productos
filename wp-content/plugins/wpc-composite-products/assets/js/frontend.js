'use strict';

jQuery(document).ready(function ($) {
    if (!$(wooco_vars.wrap_selector).length) {
        return;
    }

    $(wooco_vars.wrap_selector).each(function () {
        wooco_init_seletor();
        wooco_init($(this), 'load');
    });
});

jQuery(document).on('woosq_loaded', function () {
    // composite products in quick view popup
    wooco_init_seletor();
    wooco_init(jQuery('#woosq-popup ' + wooco_vars.wrap_selector));
});

jQuery(document).on('click touch', '.single_add_to_cart_button', function (e) {
    if (jQuery(this).hasClass('wooco-disabled')) {
        if (wooco_vars.show_alert === 'change') {
            var $wooco_wrap = jQuery(this).closest(wooco_vars.wrap_selector);
            wooco_show_alert($wooco_wrap);
        }

        e.preventDefault();
    }
});

jQuery(document).on('click touch', '.wooco-plus, .wooco-minus', function () {
    // get values
    var $number = jQuery(this).closest('.wooco-qty').find('.qty'),
        number_val = parseFloat($number.val()),
        max = parseFloat($number.attr('max')),
        min = parseFloat($number.attr('min')),
        step = $number.attr('step');

    // format values
    if (!number_val || number_val === '' || number_val === 'NaN') {
        number_val = 0;
    }
    if (max === '' || max === 'NaN') {
        max = '';
    }
    if (min === '' || min === 'NaN') {
        min = 0;
    }
    if (step === 'any' || step === '' || step === undefined ||
        parseFloat(step) === 'NaN') {
        step = 1;
    }

    // change the value
    if (jQuery(this).is('.wooco-plus')) {
        if (max && (
            max == number_val || number_val > max
        )) {
            $number.val(max);
        } else {

            if (wooco_is_int(step)) {
                $number.val(number_val + parseFloat(step));
            } else {
                $number.val((
                    number_val + parseFloat(step)
                ).toFixed(1));
            }
        }
    } else {
        if (min && (
            min == number_val || number_val < min
        )) {
            $number.val(min);
        } else if (number_val > 0) {
            if (wooco_is_int(step)) {
                $number.val(number_val - parseFloat(step));
            } else {
                $number.val((
                    number_val - parseFloat(step)
                ).toFixed(1));
            }
        }
    }

    // trigger change event
    $number.trigger('change');
});

jQuery('body').on('keyup change', '.wooco_component_product_qty_input', function () {
    var _this = jQuery(this);
    var _val = parseFloat(_this.val());
    var _min = parseFloat(_this.attr('min'));
    var _max = parseFloat(_this.attr('max'));
    var _this_composite = _this.closest(wooco_vars.wrap_selector);

    if ((
        _val < _min
    ) || isNaN(_val)) {
        _val = _min;
        jQuery(this).val(_val);
    }

    if (_val > _max) {
        _val = _max;
        jQuery(this).val(_val);
    }

    jQuery(this).closest('.wooco_component_product').attr('data-qty', _val);
    wooco_init(_this_composite);
});

function wooco_init($wooco_wrap, context = null) {
    wooco_check_ready($wooco_wrap);
    wooco_calc_price($wooco_wrap);
    wooco_save_ids($wooco_wrap);

    if (context === null || context === 'on_select' || context === wooco_vars.show_alert) {
        wooco_show_alert($wooco_wrap);
    }
}

function wooco_check_ready($wooco_wrap) {
    var $wooco_components = $wooco_wrap.find('.wooco-components');
    var $wooco_btn = $wooco_wrap.find('.single_add_to_cart_button');
    var $wooco_alert = $wooco_wrap.find('.wooco-alert');
    var is_selection = false;
    var selection_name = '';
    var is_min = false;
    var is_max = false;
    var qty = 0;
    var qty_min = parseFloat($wooco_components.attr('data-min'));
    var qty_max = parseFloat($wooco_components.attr('data-max'));

    $wooco_components.find('.wooco_component_product').each(function () {
        var _this = jQuery(this);
        var _this_id = parseInt(_this.attr('data-id'));
        var _this_qty = parseFloat(_this.attr('data-qty'));

        if (_this_id > 0) {
            qty += _this_qty;
        }

        if ((_this_id === 0) && (_this_qty > 0)) {
            is_selection = true;

            if (selection_name === '') {
                selection_name = _this.attr('data-name');
            }
        }
    });

    if (qty < qty_min) {
        is_min = true;
    }

    if (qty > qty_max) {
        is_max = true;
    }

    if (is_selection || is_min || is_max) {
        $wooco_btn.addClass('wooco-disabled');
        $wooco_alert.addClass('alert-active');

        if (is_selection) {
            $wooco_alert.addClass('alert-selection').html(wooco_vars.alert_selection.replace('[name]', '<strong>' + selection_name + '</strong>'));
            return;
        }

        if (is_min) {
            $wooco_alert.addClass('alert-min').html(wooco_vars.alert_min.replace('[min]', qty_min));
            return;
        }

        if (is_max) {
            $wooco_alert.addClass('alert-max').html(wooco_vars.alert_max.replace('[max]', qty_max));
        }
    } else {
        $wooco_alert.removeClass('alert-active alert-selection alert-min alert-max').html('');
        $wooco_btn.removeClass('wooco-disabled');
    }
}

function wooco_show_alert($wooco_wrap) {
    var $wooco_alert = $wooco_wrap.find('.wooco-alert');

    if ($wooco_alert.hasClass('alert-active')) {
        $wooco_alert.slideDown();
    } else {
        $wooco_alert.slideUp();
    }
}

function wooco_init_seletor() {
    if (wooco_vars.selector === 'ddslick') {
        jQuery('.wooco_component_product_select').each(function () {
            var _this = jQuery(this);
            var _this_selection = _this.closest('.wooco_component_product_selection');
            var _this_component = _this.closest('.wooco_component_product');
            var _this_composite = _this.closest(wooco_vars.wrap_selector);
            _this_selection.data('select', 0);

            _this.ddslick({
                width: '100%',
                onSelected: function (data) {
                    var _this_selection_select = _this_selection.data('select');
                    var _selected = jQuery(data.original[0].children[data.selectedIndex]);
                    wooco_selected(_selected, _this_selection, _this_component);

                    if (_this_selection_select > 0) {
                        wooco_init(_this_composite, 'on_select');
                    } else {
                        // selected on init_selector
                        wooco_init(_this_composite, 'selected');
                    }

                    _this_selection.data('select', _this_selection_select + 1);
                }
            });
        });
    } else if (wooco_vars.selector === 'select2') {
        jQuery('.wooco_component_product_select').each(function () {
            var _this = jQuery(this);
            var _this_selection = _this.closest('.wooco_component_product_selection');
            var _this_component = _this.closest('.wooco_component_product');
            var _this_composite = _this.closest(wooco_vars.wrap_selector);

            if (_this.val() !== '') {
                var _selected_default = jQuery("option:selected", this);

                wooco_selected(_selected_default, _this_selection, _this_component);
                wooco_init(_this_composite, 'selected');
            }

            _this.select2({
                templateResult: wooco_select2_state,
                width: '100%',
                containerCssClass: 'wpc-select2-container',
                dropdownCssClass: 'wpc-select2-dropdown'
            });
        });

        jQuery('.wooco_component_product_select').on('select2:select', function (e) {
            var _this = jQuery(this);
            var _this_selection = _this.closest('.wooco_component_product_selection');
            var _this_component = _this.closest('.wooco_component_product');
            var _this_composite = _this.closest(wooco_vars.wrap_selector);
            var _selected = jQuery(e.params.data.element);

            wooco_selected(_selected, _this_selection, _this_component);
            wooco_init(_this_composite, 'on_select');
        });
    } else {
        jQuery('.wooco_component_product_select').each(function () {
            //check on start
            var _this = jQuery(this);
            var _this_selection = _this.closest('.wooco_component_product_selection');
            var _this_component = _this.closest('.wooco_component_product');
            var _this_composite = _this.closest(wooco_vars.wrap_selector);
            var _selected = jQuery("option:selected", this);

            wooco_selected(_selected, _this_selection, _this_component);
            wooco_init(_this_composite, 'selected');
        });

        jQuery('body').on('change', '.wooco_component_product_select', function () {
            //check on select
            var _this = jQuery(this);
            var _this_selection = _this.closest('.wooco_component_product_selection');
            var _this_component = _this.closest('.wooco_component_product');
            var _this_composite = _this.closest(wooco_vars.wrap_selector);
            var _selected = jQuery("option:selected", this);

            wooco_selected(_selected, _this_selection, _this_component);
            wooco_init(_this_composite, 'on_select');
        });
    }
}

function wooco_selected(selected, selection, component) {
    var selected_id = selected.attr('value');
    var selected_pid = selected.attr('data-pid');
    var selected_price = selected.attr('data-price');
    var selected_link = selected.attr('data-link');
    var selected_img = selected.attr('data-imagesrc');
    var selected_desc = selected.attr('data-description');

    component.attr('data-id', selected_id);
    component.attr('data-price', selected_price);

    if (selected_pid === '0') {
        // get parent ID for quick view
        selected_pid = selected_id;
    }

    if (wooco_vars.product_link !== 'no') {
        selection.find('.wooco_component_product_link').remove();
        if (selected_link !== '') {
            if (wooco_vars.product_link === 'yes_popup') {
                selection.append('<a class="wooco_component_product_link woosq-btn" data-id="' + selected_pid + '" href="' + selected_link + '" target="_blank"> &nbsp; </a>');
            } else {
                selection.append('<a class="wooco_component_product_link" href="' + selected_link + '" target="_blank"> &nbsp; </a>');
            }
        }
    }

    component.find('.wooco_component_product_image').html('<img src="' + selected_img + '"/>');
    component.find('.wooco_component_product_price').html(selected_desc);

    jQuery(document).trigger('wooco_selected', [selected, selection, component]);
}

function wooco_select2_state(state) {
    if (!state.id) {
        return state.text;
    }

    var $state = new Object();

    if (jQuery(state.element).attr('data-imagesrc') !== '') {
        $state = jQuery(
            '<span class="image"><img src="' + jQuery(state.element).attr('data-imagesrc') + '"/></span><span class="info"><span>' + state.text + '</span> <span>' + jQuery(state.element).attr('data-description') + '</span></span>'
        );
    } else {
        $state = jQuery(
            '<span class="info"><span>' + state.text + '</span> <span>' + jQuery(state.element).attr('data-description') + '</span></span>'
        );
    }

    return $state;
}

function wooco_calc_price($wooco_wrap) {
    var $wooco_components = $wooco_wrap.find('.wooco-components');
    var $wooco_total = $wooco_wrap.find('.wooco-total');
    var total = 0;

    if ((
        $wooco_components.attr('data-pricing') === 'only'
    ) && (
        $wooco_components.attr('data-price') !== ''
    )) {
        total = Number($wooco_components.attr('data-price'));
    } else {
        // calc price
        $wooco_components.find('.wooco_component_product').each(function () {
            var _this = jQuery(this);

            if ((
                _this.attr('data-price') > 0
            ) && (
                _this.attr('data-qty') > 0
            )) {
                total += Number(_this.attr('data-price')) * Number(_this.attr('data-qty'));
            }
        });

        // discount
        if ((
            $wooco_components.attr('data-percent') > 0
        ) && (
            $wooco_components.attr('data-percent') < 100
        )) {
            total = total * (
                100 - Number($wooco_components.attr('data-percent'))
            ) / 100;
        }

        if ($wooco_components.attr('data-pricing') === 'include') {
            total += Number($wooco_components.attr('data-price'));
        }
    }

    var total_html = '<span class="woocommerce-Price-amount amount">';
    var total_formatted = wooco_format_money(total, wooco_vars.price_decimals, '', wooco_vars.price_thousand_separator, wooco_vars.price_decimal_separator);

    switch (wooco_vars.price_format) {
        case '%1$s%2$s':
            //left
            total_html += '<span class="woocommerce-Price-currencySymbol">' + wooco_vars.currency_symbol + '</span>' + total_formatted;
            break;
        case '%1$s %2$s':
            //left with space
            total_html += '<span class="woocommerce-Price-currencySymbol">' + wooco_vars.currency_symbol + '</span> ' + total_formatted;
            break;
        case '%2$s%1$s':
            //right
            total_html += total_formatted + '<span class="woocommerce-Price-currencySymbol">' + wooco_vars.currency_symbol + '</span>';
            break;
        case '%2$s %1$s':
            //right with space
            total_html += total_formatted + ' <span class="woocommerce-Price-currencySymbol">' + wooco_vars.currency_symbol + '</span>';
            break;
        default:
            //default
            total_html += '<span class="woocommerce-Price-currencySymbol">' + wooco_vars.currency_symbol + '</span> ' + total_formatted;
    }

    total_html += '</span>';

    if ((
        $wooco_components.attr('data-pricing') !== 'only'
    ) && (
        parseFloat($wooco_components.attr('data-percent')) > 0
    ) && (
        parseFloat($wooco_components.attr('data-percent')) < 100
    )) {
        total_html += ' <small class="woocommerce-price-suffix">' + wooco_vars.saved_text.replace('[d]', wooco_round(parseFloat($wooco_components.attr('data-percent'))) + '%') + '</small>';
    }

    $wooco_total.html(wooco_vars.total_text + ' ' + total_html).slideDown();

    if (wooco_vars.change_price !== 'no') {
        // change the main price
        var price_selector = '.summary > .price';

        if ((wooco_vars.price_selector !== null) &&
            (wooco_vars.price_selector !== '')) {
            price_selector = wooco_vars.price_selector;
        }

        $wooco_wrap.find(price_selector).html(total_html);
    }

    jQuery(document).trigger('wooco_calc_price', [total, total_formatted, total_html]);
}

function wooco_save_ids($wooco_wrap) {
    var $wooco_components = $wooco_wrap.find('.wooco-components');
    var $wooco_ids = $wooco_wrap.find('.wooco-ids');
    var wooco_ids = Array();

    $wooco_components.find('.wooco_component_product').each(function () {
        var _this = jQuery(this);

        if ((
            _this.attr('data-id') > 0
        ) && (
            _this.attr('data-qty') > 0
        )) {
            wooco_ids.push(_this.attr('data-id') + '/' + _this.attr('data-qty') + '/' + _this.attr('data-new-price'));
        }
    });

    $wooco_ids.val(wooco_ids.join(','));
}

function wooco_round(num) {
    return +(
        Math.round(num + "e+2") + "e-2"
    );
}

function wooco_format_money(number, places, symbol, thousand, decimal) {
    number = number || 0;
    places = !isNaN(places = Math.abs(places)) ? places : 2;
    symbol = symbol !== undefined ? symbol : "$";
    thousand = thousand || ",";
    decimal = decimal || ".";
    var negative = number < 0 ? "-" : "",
        i = parseInt(number = Math.abs(+number || 0).toFixed(places), 10) + "",
        j = 0;
    if (i.length > 3) {
        j = i.length % 3;
    }
    return symbol + negative + (
        j ? i.substr(0, j) + thousand : ""
    ) + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousand) + (
        places ? decimal + Math.abs(number - i).toFixed(places).slice(2) : ""
    );
}

function wooco_is_int(n) {
    return n % 1 === 0;
}