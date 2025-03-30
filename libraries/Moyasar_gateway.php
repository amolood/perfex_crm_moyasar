<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Moyasar_gateway extends App_gateway
{
	public bool $processingFees = false;

    public function __construct()
    {
        $this->ci = &get_instance();

        /**
         * Call App_gateway __construct function
         */
        //parent::__construct();
        
        /**
         * REQUIRED
         * Gateway unique id
         * The ID must be alpha/alphanumeric
         */
        $this->setId('moyasar');

        /**
         * REQUIRED
         * Gateway name
         */
        $this->setName('Moyasar');

        /**
         * Gateway settings
         */
        $this->setSettings(
            [
                [
                    'name'      => 'moyasar_secret_key',
                    'encrypted' => false,
                    'label'     => 'Secret key',
                ],
                [
                    'name'             => 'currencies',
                    'label'            => 'settings_paymentmethod_currencies',
                    'default_value'    => 'USD',
                ]
            ]
        );
    }

    //getSetting


    /**
     * REQUIRED FUNCTION
     * @param  array $data
     * @return mixed
     */
    public function process_payment($data)
    {


        $this->ci->session->set_userdata(['moyasar_total' => $data['amount']]);

        redirect(
            site_url('moyasar/payment?invoiceid=' . $data['invoiceid'] . '&hash=' . $data['invoice']->hash)
        );


    }
    

}
