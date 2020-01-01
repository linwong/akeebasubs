<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * User information display field
 * Use it $this->loadAnyTemplate('admin:com_akeebasubs/Common/ShowPaymentStatus', $params)
 *
 * $params is an array defining the following keys (they are expanded into local scope vars automatically):
 *
 * @var \FOF30\Model\DataModel   $item  The current row
 * @var string                   $field The name of the field in the current row containing the value
 * @var string                   $processorField
 * @var string                   $processorKeyField
 * @var string                   $uaField
 * @var string                   $mobileField
 * @var string                   $class
 *
 * Variables made automatically available to us by FOF:
 *
 * @var \FOF30\View\DataView\Raw $this
 */

defined('_JEXEC') or die;

/** @var \Akeeba\Subscriptions\Admin\Model\Subscriptions $this */

// Get field parameters
$defaultParams = [
	'processorField'    => 'processor',
	'processorKeyField' => 'processor_key',
	'uaField'           => 'ua',
	'mobileField'       => 'mobile',
	'class'             => '',
];

foreach ($defaultParams as $paramName => $paramValue)
{
	if (!isset(${$paramName}))
	{
		${$paramName} = $paramValue;
	}
}

unset($defaultParams, $paramName, $paramValue);

// Initialization
$stateValue   = $item->getFieldValue($field);
$stateLower   = strtolower($stateValue);
$stateLabel   = htmlspecialchars(JText::_('COM_AKEEBASUBS_SUBSCRIPTION_STATE_' . $stateValue));
$processor    = htmlspecialchars($item->{$processorField});
$processorKey = htmlspecialchars($item->{$processorKeyField});
$mobile       = $item->{$mobileField};
$ua           = $item->{$uaField};
$labelClass   = $mobile ? 'green' : 'grey';
$iconClass    = $mobile ? 'akion-android-phone-portrait' : 'akion-android-desktop';

switch ($stateValue)
{
	default:
	case 'N':
		$color = '#514F50';
		break;

	case 'P':
		$color = '#F0AD4E';
		break;

	case 'C':
		$color = '#93C34E';
		break;

	case 'X':
		$color = '#E2363C';
		break;
}

?>
<span class="akpayment-icon-state_{{ strtolower($stateValue) }} hasTip" style="font-size: 18pt; color: {{ $color }}"
	  title="{{{ $stateLabel }}}::{{{ $processor }}} &bull; {{ $processorKey }}">
</span>

<span class="akeebasubs-subscription-processor">
	@if (($processor == 'paddle') && !empty($item->cancel_url) && !empty($item->update_url))
		<span class="akpayment-icon-recuring hasTip" title="@lang('COM_AKEEBASUBS_LEVELS_FIELD_RECURRING')"></span>
	@endif

	{{{ $processor }}}

	@if ($processor == 'paddle')
		@if ($item->payment_method == 'unknown')
			<span class="akpayment-icon-unknown hasTip" title="@lang('COM_AKEEBASUBS_SUBSCRIPTION_PAYMENT_TYPE_UNKNOWN')"></span>
		@elseif ($item->payment_method == 'apple-pay')
			<span class="akpayment-icon-apple hasTip" title="@lang('COM_AKEEBASUBS_SUBSCRIPTION_PAYMENT_TYPE_APPLE')"></span>
		@elseif ($item->payment_method == 'card')
			<span class="akion-card hasTip" title="@lang('COM_AKEEBASUBS_SUBSCRIPTION_PAYMENT_TYPE_CARD')"></span>
		@elseif ($item->payment_method == 'free')
			<span class="akion-beer hasTip" title="@lang('COM_AKEEBASUBS_SUBSCRIPTION_PAYMENT_TYPE_FREE')"></span>
		@elseif ($item->payment_method == 'paypal')
			<span class="akpayment-icon-paypal hasTip" title="@lang('COM_AKEEBASUBS_SUBSCRIPTION_PAYMENT_TYPE_PAYPAL')"></span>
		@elseif ($item->payment_method == 'wire-transfer')
			<span class="akpayment-icon-bank hasTip" title="@lang('COM_AKEEBASUBS_SUBSCRIPTION_PAYMENT_TYPE_WIRE')"></span>
		@endif
	@endif

</span>

@if(!empty($ua))
    <span class="akeebasubs-subscription-ua hasTip" title="@lang('COM_AKEEBASUBS_SUBSCRIPTIONS_UA')::{{{ $ua }}}">
        <span class="akeeba-label--{{{ $labelClass }}}"><span class="{{{ $iconClass }}}"></span></span>
</span>
@endif
