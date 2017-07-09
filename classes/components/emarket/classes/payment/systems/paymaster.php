<?php

class paymasterPayment extends payment
{

    const HASH_ALG = 'sha256';

    /**
     * Function validate amount order
     * In this case we make off this function
     * @param NULL
     * @return boolean
     */
    public function validate()
    {
        return true;
    }

    /**
     * Function create payment form and create order
     * @param $template
     * @return NULL
     */
    public function process($template = null)
    {

        $this->order->order();
        $merchant_id = $this->object->getValue('lmi_merchant_id');
        $sign_method = $this->getSignMethod($this->object->getValue('sign_method'));

        $secret_key = htmlspecialchars_decode($this->object->getValue('secret'));
        $currency = strtoupper(mainConfiguration::getInstance()->get('system', 'default-currency'));
        $amount = number_format($this->order->getActualPrice(), 2, '.', '');
        if ($currency == 'RUR') {
            $currency = 'RUB';
        }
        $plain_sign = $merchant_id . $this->order->id . $amount . $currency . $secret_key;

        $params = array();
        $params['LMI_MERCHANT_ID'] = $merchant_id;
        $params['LMI_PAYMENT_AMOUNT'] = $amount;
        $params['LMI_CURRENCY'] = $currency;
        $params['LMI_PAYMENT_NO'] = $this->order->id;
        $params['LMI_PAYMENT_DESC'] = 'Оплата товаров по заказу №' . $this->order->id;
        $params['SIGN'] = base64_encode(hash($sign_method, $plain_sign, true));


        $items = $this->order->getItems();

        //Добавляем продукты в форму
        foreach ($items as $key => $item) {
            $params["LMI_SHOPPINGCART.ITEM[{$key}].NAME"] = $item->getName();
            $params["LMI_SHOPPINGCART.ITEM[{$key}].QTY"] = $item->getAmount();
            $params["LMI_SHOPPINGCART.ITEM[{$key}].PRICE"] = $item->getItemPrice() - $item->getDiscount();
            $params["LMI_SHOPPINGCART.ITEM[{$key}].TAX"] = $this->UmiToPaymasterTaxConversion($item->getTaxRateId());
        }

        $deliveryPrice = number_format($this->order->getDeliveryPrice(), 2, '.', '');

        //Добавляем доставку в форму
        if ($deliveryPrice > 0) {
            $key++;
            $params["LMI_SHOPPINGCART.ITEM[{$key}].NAME"] = "Доставка заказа №".$this->order->id;
            $params["LMI_SHOPPINGCART.ITEM[{$key}].QTY"] = 1;
            $params["LMI_SHOPPINGCART.ITEM[{$key}].PRICE"] = $deliveryPrice;
            $params["LMI_SHOPPINGCART.ITEM[{$key}].TAX"] = $this->UmiToPaymasterTaxConversion($this->object->getValue('delivery_vat'));
        }

        $params['key'] = $key;

        $params['param0'] = $this->order->id;

        $this->order->setPaymentStatus('initialized');

        list($templateString) = def_module::loadTemplates("emarket/payment/paymaster/" . $template, "form_block");
        return def_module::parseTemplate($templateString, $params);
    }

    public static function getOrderId()
    {
        return (int)getRequest("LMI_PAYMENT_NO");
    }

    /**
     * Получаем метод подписи запроса
     * @param $method
     * @return string
     */
    public function getSignMethod($method)
    {
        switch ($method) {
            case 'sha256':
                return 'sha256';
                break;
            case 'md5':
                return 'md5';
                break;
            case 'sha1':
                return 'sha1';
                break;
            default:
                paymasterPayment::HASH_ALG;
                break;
        }
    }

    /**
     * НДС конверсия из Umi в формат Paymaster
     * @param null $objectId
     * @return mixed|string
     */
    public function UmiToPaymasterTaxConversion($objectId = null)
    {
        $payMasterTaxArray = array(
            'Без НДС' => 'no_vat',
            'НДС по расчетной ставке 10/110' => 'vat110',
            'НДС по расчетной ставке 18/118' => 'vat118', //внимание! тут может быть глюк!!!
            'НДС по ставке 0%' => 'vat0',
            'НДС по ставке 10%' => 'vat10',
            'НДС по ставке 18%' => 'vat18',
        );

        if (is_null($objectId)) {
            return 'no_vat';
        } else {

            $objectName = umiObjectsCollection::getInstance()->getObject($objectId)->name;
            $objectName = trim(preg_replace("/ {2,}/", " ", $objectName)); //удаляем двойные и лидирующие пробелы (глюк Umi)

            if (isset($payMasterTaxArray[$objectName])) {
                return $payMasterTaxArray[$objectName];
            } else {
                return 'no_vat';
            }

        }

    }

    /**
     * Function validate response from PayMaster
     * @param NULL
     * @return NULL
     */
    public function poll()
    {
        $buffer = outputBuffer::current();
        $buffer->clear();
        $buffer->contentType("text/plain");

        if ($this->hash_validation() && $this->sign_validation()) {
            $this->order->setPaymentStatus("accepted");
            $this->order->payment_document_num = getRequest('LMI_SYS_PAYMENT_ID');
            $this->order->commit();
            $buffer->push("OK");
        } else {
            $buffer->push("failed");
        }
        $buffer->end();
    }

    /**
     * Function validation hash with change hash methods
     * @param NULL
     * @return boolean
     */
    private function hash_validation()
    {
        $lmi_hash_post = getRequest('LMI_HASH');
        if (isset($lmi_hash_post)) {
            $secret_key = htmlspecialchars_decode($this->object->getValue('secret'));
            $plain_string = getRequest("LMI_MERCHANT_ID") . ";" . getRequest("LMI_PAYMENT_NO") . ";";
            $plain_string .= (getRequest("LMI_SYS_PAYMENT_ID") . ";" . getRequest("LMI_SYS_PAYMENT_DATE") . ";");
            $plain_string .= (getRequest("LMI_PAYMENT_AMOUNT") . ";" . getRequest("LMI_CURRENCY") . ";" . getRequest("LMI_PAID_AMOUNT") . ";");
            $plain_string .= (getRequest("LMI_PAID_CURRENCY") . ";" . getRequest("LMI_PAYMENT_SYSTEM") . ";");
            $plain_string .= (getRequest("LMI_SIM_MODE") . ";" . $secret_key);
            $hash = base64_encode(hash(paymasterPayment::HASH_ALG, $plain_string, true));
            return strcasecmp($lmi_hash_post, $hash) == 0;
        }
        return false;
    }

    /**
     * Function validation sign with change hash methods
     * @param NULL
     * @return boolean
     */
    private function sign_validation()
    {
        $lmi_sign = getRequest('SIGN');
        $secret_key = htmlspecialchars_decode($this->object->getValue('secret'));
        $plain_sign = getRequest("LMI_MERCHANT_ID") . getRequest("LMI_PAYMENT_NO") . getRequest("LMI_PAYMENT_AMOUNT") . getRequest("LMI_CURRENCY") . $secret_key;
        $sign = base64_encode(hash(paymasterPayment::HASH_ALG, $plain_sign, true));
        return strcasecmp($lmi_sign, $sign) == 0;
    }

}

;
?>