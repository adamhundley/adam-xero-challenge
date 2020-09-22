<?php

use XeroAPI\XeroPHP\Models\Accounting\Item as XeroItem;
use XeroAPI\XeroPHP\Models\Accounting\Purchase;

class Item
{
    public function __construct(string $name, int $price)
    {
        $sales = (new Purchase())
            ->setUnitPrice($price);

        $this->xero_item  = (new XeroItem())
            ->setName($name)
            ->setCode("SKU - $name")
            ->setIsTrackedAsInventory(false)
            ->setSalesDetails($sales);
    }

    public function getXeroItem(): XeroItem
    {
        return $this->xero_item;
    }
}
