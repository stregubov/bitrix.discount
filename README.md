# bitrix.discount
Персональная скидка из HL блока

В php_interface/init.php подключаем

```php
$eventManager = \Bitrix\Main\EventManager::getInstance();

$eventManager->addEventHandlerCompatible("sale", "OnCondSaleActionsControlBuildList",
    ["\Personal", "GetControlDescr"]);
```
