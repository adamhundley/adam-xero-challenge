<?php
require_once('item.php');

use XeroAPI\XeroPHP\AccountingObjectSerializer;
use XeroAPI\XeroPHP\Models\Accounting\Contact;
use XeroAPI\XeroPHP\Models\Accounting\Invoice;
use XeroAPI\XeroPHP\Models\Accounting\Invoices;
use XeroAPI\XeroPHP\Models\Accounting\Items;
use XeroAPI\XeroPHP\Models\Accounting\LineItem;
use XeroAPI\XeroPHP\Models\Accounting\Payment;
use XeroAPI\XeroPHP\Models\Accounting\TaxType;

class TaskDispatch
{
    public function __construct($xero_tenant_id, $apiInstance)
    {
        $this->xero_tenant_id = $xero_tenant_id;
        $this->api_instance = $apiInstance;
    }

    public function createItems(array $item_data)
    {
        $this->item_data = $item_data;
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
            $this->api_instance->createItems($this->xero_tenant_id, $this->items_object, true);
        } catch (\XeroAPI\XeroPHP\ApiException $e) {
            $error = AccountingObjectSerializer::deserialize(
                $e->getResponseBody(),
                '\XeroAPI\XeroPHP\Models\Accounting\Error',
                []
            );
            print_r($error);
        }
    }

    public function createContact()
    {
        $this->contact = (new Contact())
            ->setName('Rod Drury')
            ->setFirstName("Rod")
            ->setLastName("Drury");

        try {
            $this->api_instance->createContacts(
                $this->xero_tenant_id,
                ['contacts' => [$this->contact]]
            );
        } catch (\XeroAPI\XeroPHP\ApiException $e) {
            $error = AccountingObjectSerializer::deserialize(
                $e->getResponseBody(),
                '\XeroAPI\XeroPHP\Models\Accounting\Error',
                []
            );
            print_r($error);
        }
    }

    public function createLineItems()
    {
        $this->line_items = [];
        foreach ($this->items_object as $item) {
            $name = $item->getName();
            $this->line_items[] = (new LineItem())
                ->setItemCode($item->getCode())
                ->setAccountCode($this->account->getCode())
                ->setQuantity($this->item_data[$name]['quantity'])
                ->setDescription("4 {$name}")
                ->setTaxType(TaxType::NONE)
                ->setUnitAmount($item->getSalesDetails()->getUnitPrice());
        }
    }

    public function createInvoice()
    {
        $invoice = (new Invoice())
            ->setContact($this->contact)
            ->setType(Invoice::TYPE_ACCREC)
            ->setStatus(Invoice::STATUS_AUTHORISED)
            ->setReference('Xero-Adam')
            ->setDueDate(new DateTime())
            ->setLineItems($this->line_items);
        $invoices = (new Invoices())
            ->setInvoices([$invoice]);
        try {
            $invoices = $this->api_instance->createInvoices(
                $this->xero_tenant_id,
                $invoices
            );
            $this->invoice = $invoices->getInvoices()[0] ?? null;
        } catch (\XeroAPI\XeroPHP\ApiException $e) {
            $error = AccountingObjectSerializer::deserialize(
                $e->getResponseBody(),
                '\XeroAPI\XeroPHP\Models\Accounting\Error',
                []
            );
            print_r($error);
        }
    }

    public function createPayment()
    {
        $payment = (new Payment())
            ->setInvoice($this->invoice)
            ->setAccount($this->account)
            ->setAmount($this->invoice->getTotal());
        try {
            $this->api_instance->createPayment($this->xero_tenant_id, $payment);
        } catch (\XeroAPI\XeroPHP\ApiException $e) {
            $error = AccountingObjectSerializer::deserialize(
                $e->getResponseBody(),
                '\XeroAPI\XeroPHP\Models\Accounting\Error',
                []
            );
            print_r($error);
        }
    }

    public function getAccount()
    {
        try {
            $accounts = $this->api_instance->getAccount($this->xero_tenant_id, '400');
            $this->account = $accounts->getAccounts()[0] ?? null;
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
