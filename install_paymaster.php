<?php

$className = "paymaster";
$paymentName = "Paymaster";

include "standalone.php";

$objectTypesCollection = umiObjectTypesCollection::getInstance();
$internalTypeId = $objectTypesCollection->getTypeIdByGUID("emarket-paymenttype");


$sel = new selector('objects');
$sel->types('object-type')->id($internalTypeId);
$sel->where('class_name')->equals($className);
$sel->limit(0, 1);

$bAdd = $sel->length() == 0;


if($bAdd) {
    $objectsCollection = umiObjectsCollection::getInstance();

    $parentTypeId = $objectTypesCollection->getTypeIdByGUID("emarket-payment");

    $typeId = $objectTypesCollection->addType($parentTypeId, $paymentName);
    $objectType = $objectTypesCollection->getType($typeId);

    $groupdId = $objectType->addFieldsGroup('settings', 'Параметры', true, true);
    $group = $objectType->getFieldsGroupByName('settings');

    $fieldsCollection = umiFieldsCollection::getInstance();

    $fieldTypesCollection = umiFieldTypesCollection::getInstance();
    $typeBoolean = $fieldTypesCollection->getFieldTypeByDataType('boolean');
    $typeString = $fieldTypesCollection->getFieldTypeByDataType('string');
    $typeRelation = $fieldTypesCollection->getFieldTypeByDataType('relation');


    $merchantFieldId = $fieldsCollection->addField('lmi_merchant_id', 'Идентификатор продавца', $typeString->getId());
    $fieldMerchant = $fieldsCollection->getField($merchantFieldId);
    $fieldMerchant->setIsRequired(true);
    $fieldMerchant->setIsInSearch(false);
    $fieldMerchant->setIsInFilter(false);
    $fieldMerchant->commit();
    $group->attachField($merchantFieldId);

    $secretFieldId = $fieldsCollection->addField('secret', 'Секретное слово', $typeString->getId());
    $fieldSecret = $fieldsCollection->getField($secretFieldId);
    $fieldSecret->setIsRequired(true);
    $fieldSecret->setIsInSearch(false);
    $fieldSecret->setIsInFilter(false);
    $fieldMerchant->commit();
    $group->attachField($secretFieldId);

    $fieldSignMethodId = $fieldsCollection->addField('sign_method', 'Метод шифрования', $typeString->getId());
    $fieldSignMethod = $fieldsCollection->getField($fieldSignMethodId);
    $fieldSignMethod->setIsRequired(true);
    $fieldSignMethod->setIsInSearch(false);
    $fieldSignMethod->setIsInFilter(false);
    $fieldSignMethod->setTip('Укажите md5, sha256 или sha1');
    $fieldSignMethod->commit();
    $group->attachField($fieldSignMethodId);

    $fieldDeliveryVatId = $fieldsCollection->addField('delivery_vat', 'Ставка НДС для доставки', $typeRelation->getId());
    $fieldDeliveryVat = $fieldsCollection->getField($fieldDeliveryVatId);
    $fieldDeliveryVat->setGuideId(getVatGuideId());
    $fieldDeliveryVat->setIsRequired(true);
    $fieldDeliveryVat->setIsInSearch(false);
    $fieldDeliveryVat->setIsInFilter(false);
    $fieldDeliveryVat->commit();
    $group->attachField($fieldDeliveryVatId);


    $fieldSuccessOrderStatusId = $fieldsCollection->addField('success_order_status', 'Статус заказа в случае успешной оплаты', $typeRelation->getId());
    $fieldSuccessOrderStatus = $fieldsCollection->getField($fieldSuccessOrderStatusId);
    $fieldSuccessOrderStatus->setGuideId(getGuidOrderStatusesId());
    $fieldSuccessOrderStatus->setIsRequired(true);
    $fieldSuccessOrderStatus->setIsInSearch(false);
    $fieldSuccessOrderStatus->setIsInFilter(false);
    $fieldSuccessOrderStatus->commit();
    $group->attachField($fieldSuccessOrderStatusId);

    $internalObjectId = $objectsCollection->addObject($paymentName, $internalTypeId);
    $internalObject = $objectsCollection->getObject($internalObjectId);
    $internalObject->setValue("class_name", $className); // имя класса для реализации

    $internalObject->setValue("payment_type_id", $typeId);
    $internalObject->setValue("payment_type_guid", "user-emarket-payment-" . $typeId);
    $internalObject->commit();

    $objectType = $objectTypesCollection->getType($typeId);
    $objectType->setGUID($internalObject->getValue("payment_type_guid"));
    $objectType->commit();

    echo "Готово!";
} else {
    echo "Способ оплаты с классом ". $className. " уже существует";
}


/**
 * Возвращаем Id справочника статусов заказа
 * @return mixed
 */
function getGuidOrderStatusesId()
{
    $sel = new selector('objects');
    $sel->types('object-type')->name('emarket', 'order_status');
    $sel->option('no-length')->value(true);
    return $sel->first->getTypeId();
}

/**
 * Возвращаем Id справочника со значениями НДС
 * @return mixed
 */
function getVatGuideId()
{
    return umiObjectTypesCollection::getInstance()
        ->getTypeIdByGUID('tax-rate-guide');
}
