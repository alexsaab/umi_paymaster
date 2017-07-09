**Инструкция по настройки CMS Umi для работы с системой оплаты PayMaster и онлайн кассой** 

Необходимо произвести настройки в личном кабинете PayMaster:
1.	 Список сайтов -> Настройки -> Блок "Технические параметры"
 Выберите "Тип подписи", установите SHA256
 Введите "Секретный ключ"
               Сохраните изменения
2.	 Список сайтов -> Настройки -> Блок "Обратные вызовы"
               Payment Notification: выбрать POST-запрос и в появившемся поле вставить отредактированную ссылку:
    https://ВАШ_САЙТ/emarket/gateway/
               Success redirect: выбрать POST-запрос и в появившемся поле вставить отредактированную ссылку:
    https://ВАШ_САЙТ/emarket/purchase/result/successful/
               Failure redirect: выбрать POST-запрос и в появившемся поле вставить отредактированную ссылку:
    https://ВАШ_САЙТ/emarket/purchase/result/fail/
1.	Загружаем архив в корень сайта, распаковываем
2.	Открываем ссылку https://ВАШ_САЙТ/install_paymaster.php, должна появиться надпись «Готово!»
3.	Далее переходим в раздел «Интернет магазин», вкладка «Оплата».
4.	Нажимаем «Добавить способ".
5.	Из списка выбираем PayMaster.
6.	Откроются настройки метода оплаты Paymaster  
7.	Необходимо указать название «PayMaster»
8.	Тип оплаты: из выпадающего списка необходимо выбрать Paymaster
9.	Идентификатор продавца: его необходимо взять из личного кабинета PayMaster (раздел «Список сайтов»).
10.	Секретное слово: Задается в личном кабинете PayMaster («Список сайтов» -> «Настройки» -> «Технические параметры» -> поле «Секретный ключ»).
11.	Тип шифрования можете ввести либо “md5”, либо “sha256”, либо “sha1”, по умолчанию метод шифрования “sha256”, но все должно быть одинаково, что в системе Paymaster что на сайте под управлением CMS Umi
12.	Ставка НДС для доставки: необходима для оплаты и онлайн кассы в соответствии с федеральным законом (ФЗ) 54. Ставки НДС для продуктов берутся отдельно из карточек товаров (все нужно заполнить вручную).
