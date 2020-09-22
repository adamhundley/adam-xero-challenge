<?php
require_once('item.php');
use XeroAPI\XeroPHP\Models\Accounting\Items;
class TaskDispatch
{
    public function __construct($xero_tenant_id, $apiInstance)
    {
        $this->xero_tenant_id = $xero_tenant_id;
        $this->api_instance = $apiInstance;
    }

    public function createItems(array $item_data)
    {
        $items = [];
        foreach ($item_data as $item) {
            $item_object = new Item(
                $item['name'],
                $item['price'],
            );
            $items[] = $item_object->getXeroItem();
        }

        $this->items_object = (new Items())->setItems($items);

        try {
            $this->api_instance->createItems($this->xero_tenant_id, $this->items_object);
        } catch (\XeroAPI\XeroPHP\ApiException $e) {
            $error = AccountingObjectSerializer::deserialize(
                $e->getResponseBody(),
                '\XeroAPI\XeroPHP\Models\Accounting\Error',
                []
            );
            print_r($error);
        }
    }
}
