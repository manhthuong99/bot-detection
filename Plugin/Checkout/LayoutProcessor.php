<?php
namespace Eric\BotDetection\Plugin\Checkout;

use Magento\Payment\Model\Config;

class LayoutProcessor
{
    /**
     * @var Config
     */
    private $paymentModelConfig;

    private $fields = [
        'firstname',
        'lastname',
        'company',
        'telephone',
        'postcode',
        'city',
    ];

    /**
     * @param Config $paymentModelConfig
     */
    public function __construct(
        Config $paymentModelConfig
    ) {
        $this->paymentModelConfig = $paymentModelConfig;
    }

    /**
     * @param \Magento\Checkout\Block\Checkout\LayoutProcessor $subject
     * @param mixed $result
     * @param mixed $jsLayout
     * @return mixed
     */
    public function afterProcess(
        \Magento\Checkout\Block\Checkout\LayoutProcessor $subject,
        $result,
        $jsLayout
    ) {
        foreach ($this->fields as $field) {
            $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']
            ['shippingAddress']['children']['shipping-address-fieldset']['children'][$field]['validation']['validate-special-character'] = true;
        }
        $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']
        ['shippingAddress']['children']['shipping-address-fieldset']['children']['street']['children'][0]['validation']['validate-special-character'] = true;
        $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']
        ['shippingAddress']['children']['shipping-address-fieldset']['children']['street']['children'][1]['validation']['validate-special-character'] = true;

        $paymentMethods = $this->paymentModelConfig->getActiveMethods();
        foreach ($paymentMethods as $paymentMethod) {
            $method = $paymentMethod->getCode();
            $paymentForm = $method . '-form';
            foreach ($this->fields as $field) {
                $result['components']['checkout']['children']['steps']['children']['billing-step']['children']
                ['payment']['children']['payments-list']['children'][$paymentForm]['children']['form-fields']['children'][$field]['validation']['validate-special-character'] = true;
            }
            $result['components']['checkout']['children']['steps']['children']['billing-step']['children']
            ['payment']['children']['payments-list']['children'][$paymentForm]['children']['form-fields']['children']['street']['children'][0]['validation']['validate-special-character'] = true;
            $result['components']['checkout']['children']['steps']['children']['billing-step']['children']
            ['payment']['children']['payments-list']['children'][$paymentForm]['children']['form-fields']['children']['street']['children'][1]['validation']['validate-special-character'] = true;
        }
        return $result;
    }
}
