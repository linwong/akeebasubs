/*
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * Setup (required for Joomla! 3)
 */
if (typeof (akeeba) === "undefined")
{
    var akeeba = {};
}

if (typeof (akeeba.jQuery) === "undefined")
{
    akeeba.jQuery = window.jQuery.noConflict();
}

var akeebasubsMessageUrl = window.location;

function akeebasubsLevelToggleDetails()
{
    (function ($) {
        var elDetails = $("#akeebasubs-column-product-description");

        if (!elDetails)
        {
            return;
        }

        if (elDetails.css("display") == "none")
        {
            elDetails.show("slow");

            return;
        }

        elDetails.hide("slow");
    })(akeeba.jQuery);
}

function akeebasubsLevelToggleCoupon(e)
{
    (function ($) {
        var elContainer = $("#akeebasubs-coupon-code-container");

        if (!elContainer)
        {
            return;
        }

        if (elContainer.css("display") == "none")
        {
            e          = e || window.event;
            var target = e.target || e.srcElement;
            elContainer.show("fast");
            $(target).hide("fast");

            return;
        }

        elContainer.hide("fast");
    })(akeeba.jQuery);
}

function validateForm()
{
    (function ($) {
        var signupForm    = $("#signupForm");
        signupForm.attr('action', window.location);
        signupForm.submit();
    })(akeeba.jQuery);
}

function akeebaSubscriptionsStartPayment()
{
    (function ($) {
        var formData = {
            coupon: $('#coupon').val(),
            accept_terms: $('#accept_terms').is(':checked') ? 1 : 0
        };

        if ($('#name'))
        {
            formData['name'] = $('#name').val();
            formData['username'] = $('#username').val();
            formData['password'] = $('#password').val();
            formData['password2'] = $('#password2').val();
            formData['email'] = $('#email').val();
            formData['email2'] = $('#email2').val();
        }

        $.ajax({
            url: $('#signupForm').attr('action'),
            data: formData,
            type: "POST",
            dataType: "json"
        }).done(function(ret) {
            console.log(ret);

            if (ret.method === 'redirect')
            {
                if (ret.url === null)
                {
                    window.location.reload();

                    return;
                }

                window.location = ret.url;

                return;
            }

            akeebasubsMessageUrl = ret.messageUrl;

            Paddle.Checkout.open({
                override: ret.url,
                successCallback: 'akeebasubsCheckoutComplete',
                closeCallback: 'akeebasubsCheckoutClosed',
                eventCallback: 'akeebasubsCheckoutEvent'
            });
        }).fail(function(jqXHR, textStatus, errorThrown){
            window.location.reload();
        });
    })(akeeba.jQuery);

    return false;
}

/**
 * Fired when the payment is successful
 *
 * @param {Object} data
 */
function akeebasubsCheckoutComplete(data)
{
    console.log('Got checkout complete');
    console.log(data);

    window.setTimeout(function() {
        window.location = akeebasubsMessageUrl;
    }, 1000);
}

/**
 * Fired when the checkout modal closes without a successful payment
 *
 * @param {Object} data
 */
function akeebasubsCheckoutClosed(data)
{
    console.log('Got checkout closed');
    console.log(data);

    window.setTimeout(function() {
        window.location = akeebasubsMessageUrl;
    }, 1000);
}

/**
 * Executes when Paddle fires an event. Used for Google Analytics e-commerce tracking.
 *
 * @param {Object} data
 */
function akeebasubsCheckoutEvent(data)
{
    if (data.event === 'Checkout.PaymentComplete')
    {
        console.log("AkeebaSubs GACommerce: Submitting e-commerce information using ga.js");
        ga('require', 'ecommerce');
        ga('ecommerce:addTransaction', {
            'id': data.checkout.passthrough,
            'revenue': data.checkout.prices.vendor.total,
            'currency': data.checkout.prices.vendor.currency
        });
        ga('ecommerce:addItem', {
            'id': data.checkout.passthrough,
            'name': data.product.name,
            'sku': data.product.id,
            'price': data.checkout.prices.vendor.total,
            'currency': data.checkout.prices.vendor.currency
        });
        ga('ecommerce:send');
    }
}

/**
 * Localises a price using the Paddle API. Only for single products, not recurring subscriptions.
 *
 * @param   {Number}   product            Paddle product ID
 * @param   {Boolean}  allowTaxInclusive  Should I display tax inclusive pricing in grossTarget?
 * @param   {String}   grossTarget        ID of the element to display the gross price
 * @param   {String}   taxTarget          ID of the element to display the tax price
 * @param   {String}   taxContainer       ID of an element to hide if Paddle returns non-tax-inclusive price
 * @param   {String}   countryTarget      ID of the element to display the country code
 *
 * @see https://paddle.com/docs/paddlejs-localized-prices/
 */
function akeebasubsLocalisePrice(product, allowTaxInclusive, grossTarget, taxTarget, taxContainer, countryTarget)
{
    Paddle.Product.Prices(product, 1, function(prices) {
        if (grossTarget)
        {
            var elGross = document.getElementById(grossTarget);

            if (elGross !== undefined)
            {
                elGross.innerText = allowTaxInclusive ? prices.price.gross : prices.price.net;
            }
        }

        if (taxTarget)
        {
            var elTax = document.getElementById(taxTarget);

            if (elTax !== undefined)
            {
                elTax.innerText = prices.price.tax;
            }
        }

        if (taxContainer)
        {
            var elTaxContainer = document.getElementById(taxContainer);

            if (elTaxContainer !== undefined)
            {
                elTaxContainer.style.display = prices.price.tax_included ? 'block' : 'none'
            }
        }

        if (countryTarget)
        {
            var elCountry = document.getElementById(countryTarget);

            if (elCountry !== undefined)
            {
                if ('value' in elCountry)
                {
                    elCountry.value = prices.country;
                }
                else
                {
                    elCountry.innerText = prices.country;
                }
            }
        }
    });
}

(function ($) {
    $(document).ready(function () {
        // Disable form submit when ENTER is hit in the coupon field
        $("input#coupon")
            .keypress(function (e) {
                if (e.which == 13)
                {
                    validateForm();
                    return false;
                }
            });
    });
})(akeeba.jQuery);