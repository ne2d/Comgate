<?php

namespace LZaplata\Comgate;


use Nette\Object;

class Payment extends Object
{
    /** @var Service */
    public $service;

    /** @var \AgmoPaymentsSimpleDatabase */
    public $paymentsDatabase;

    /** @var \AgmoPaymentsSimpleProtocol */
    public $paymentsProtocol;

    /** @var string */
    public $refId;

    /** @var float */
    public $price;

    /**
     * Payment constructor.
     * @param Service $service
     */
    public function __construct(Service $service)
    {
        if (!file_exists(__DIR__ . "/data")) {
            mkdir(__DIR__ . "/data");
        }

        $this->service = $service;
        $this->paymentsDatabase = new \AgmoPaymentsSimpleDatabase(
            dirname(__FILE__) . "/data",
            $this->service->getMerchant(),
            $this->service->getSecret()
        );
        $this->paymentsProtocol = new \AgmoPaymentsSimpleProtocol(
            $this->service->getUrl() . "/create",
            $this->service->getMerchant(),
            $this->service->getSandbox(),
            $this->service->getSecret()
        );
    }

    /**
     * @param $price
     * @throws \Exception
     */
    public function createPayment($price)
    {
        $this->refId = $this->paymentsDatabase->createNextRefId();
        $this->price = $price;

        $this->paymentsProtocol->createTransaction(
            "CZ",                                               // country
            $price,                                             // price
            $this->service->getCurrency(),                      // currency
            "payment",                                          // description
            $this->refId,                                       // refId
            null,                                               // payerId
            "STANDARD",                                         // vatPL
            "PHYSICAL",                                         // category
            "ALL",                                              // method
            "",                                                 // account
            "",                                                 // email
            "",                                                 // phone
            "",                                                 // productName
            "",                                                 // language
            $this->service->getPreauth(),                       // preauth
            false,                                              // reccuring
            null,                                               // reccuringId
            false,                                              // eetReport
            null                                                // eetData
        );
    }

    /**
     * @return Response
     * @throws \Exception
     */
    public function send()
    {
        $transId = $this->paymentsProtocol->getTransactionId();

        $this->paymentsDatabase->saveTransaction(
            $transId,                                           // transId
            $this->refId,                                       // refId
            $this->price,                                       // price
            $this->service->getCurrency(),                      // currency
            "PENDING"                                           // status
        );

        return new Response($this->paymentsProtocol, $this->service);
    }

    /**
     * @return int
     */
    public function getPayId()
    {
        return $this->paymentsProtocol->getTransactionId();
    }
}