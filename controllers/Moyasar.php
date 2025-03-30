<?php

use GuzzleHttp\Client;

class Moyasar extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('Moyasar_gateway');
        $moyasar_gateway = new Moyasar_gateway();
        $this->moyasar_secret_key = $moyasar_gateway->getSetting('moyasar_secret_key');

    }

    /**
     * Endpoint for Moyasar after visit the payout link
     * @param  string $invoiceid   invoice id
     * @param  string $hash invoice hash
     *
     * @return mixed
     */

    public function verify()
    {
        $get = $this->input->get();
        if (!isset($get['invoiceid']) || !isset($get['hash'])) {
            set_alert('danger', _l('invoice_id_not_found'));
            redirect(site_url()); // Redirect to a safe location
        }
        $invoice_id = $this->input->get('invoiceid');
        $invoice_hash = $this->input->get('hash');
        $payment_invoice_id = $this->input->get('invoice_id');

        check_invoice_restrictions($invoice_id, $invoice_hash);

        // Determine API URL and secret key based on test mode
        $apiURL = 'https://api.moyasar.com/v1/invoices/' . $payment_invoice_id;  //Correct Endpoint URL
        $this->db->where('id', $invoice_id);
        $invoice = $this->db->get(db_prefix() . 'invoices')->row();

        if (!$invoice) {
            set_alert('danger', _l('invoice_not_found'));
            redirect(site_url()); // Redirect to a safe location
        }

        try {
            $client = new Client();

            $response = $client->request('GET', $apiURL, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => "Basic " . base64_encode(trim($this->moyasar_secret_key) . ':'),
                ],
            ]);

            $moyasar = json_decode($response->getBody()->getContents());

            // **DEBUGGING: Dump the entire response for inspection**
            log_activity("Moyasar API Response (Verify): " . json_encode($moyasar, JSON_PRETTY_PRINT));  //Log activities as well

            if ($moyasar && isset($moyasar->status) && $moyasar->status == 'paid') {  //Use the correct API status checking

                $amount = (float)($moyasar->amount / 100);  //Convert back to original value.

                $success = $this->moyasar_gateway->addPayment([
                    'amount'        => $amount,
                    'invoiceid'     => $invoice_id,
                    'paymentmethod' => '',
                    'transactionid' => $payment_invoice_id,  //Use the payment ID from Moyasar.
                ]);

                set_alert($success ? 'success' : 'danger', _l($success ? 'online_payment_recorded_success' : 'online_payment_recorded_success_fail_database'));
            } else {
                $errorMessage = isset($moyasar->error->message) ? $moyasar->error->message : _l('invoice_payment_record_failed_general');
                set_alert('danger', _l('invoice_payment_record_failed') . ' <br /> ' . $errorMessage);
            }
        } catch (\Exception $e) {
            log_activity("Moyasar API Exception (Verify): " . $e->getMessage());  //Log exception as well.
            set_alert('danger', _l('payment_gateway_record_payment_fail') . ' <br /> ' . $e->getMessage());
        }

        redirect(site_url('invoice/' . $invoice_id . '/' . $invoice_hash));
    }

    public function payment()
    {
        // Check invoice restrictions and load necessary models
        $invoice_id = $this->input->get('invoiceid');
        $invoice_hash = $this->input->get('hash');

        if (!$invoice_id || !$invoice_hash) {
            set_alert('danger', _l('invoice_id_not_found'));
            redirect(site_url()); // Redirect to a safe location
        }

        check_invoice_restrictions($invoice_id, $invoice_hash);
        $this->load->model('invoices_model');
        $this->load->model('clients_model'); // Load Clients model
        $invoice = $this->invoices_model->get($invoice_id);

        if (!$invoice) {
            set_alert('danger', _l('invoice_not_found'));
            redirect(site_url()); // Redirect to a safe location
        }

        load_client_language($invoice->clientid);
        $data['invoice'] = $invoice;
        $data['amount'] = $this->session->userdata('moyasar_total');

        // Get API key and URL based on test/live mode
        $apiURL = 'https://api.moyasar.com/v1/invoices'; // Correct Endpoint URL



        // Build callback URL
        $calurl = site_url('moyasar/verify?invoiceid=' . $invoice_id . '&hash=' . $invoice_hash);
        // Set customer information
        $email = ''; // Initialize email
        $firstname = ''; // Initialize firstname
        $lastname = ''; // Initialize lastname

        if (is_client_logged_in()) {
            $contact = $this->clients_model->get_contact(get_contact_user_id());
            if ($contact && $contact->email) {
                $email = $contact->email;
                $firstname = $contact->firstname;
                $lastname = $contact->lastname;
            }
        } else {
            $contacts = $this->clients_model->get_contacts($data['invoice']->clientid);
            if ($contacts && count($contacts) > 0) {
                $contact = $contacts[0]; // Assuming the first contact is relevant
                if ($contact && isset($contact['email'])) { //Access array using []
                    $email = $contact['email'];
                    $firstname = $contact['firstname'];
                    $lastname = $contact['lastname'];
                } else {
                    $firstname = $data['invoice']->client->company ?? 'Guest';
                    $lastname = $data['invoice']->client->company ? 'Company' : 'Account';
                }
            }else {
                $firstname = $data['invoice']->client->company ?? 'Guest';
                $lastname = $data['invoice']->client->company ? 'Company' : 'Account';
            }
        }

        try {
            // Prepare API payload
            $postFields = [
                'amount' => intval($data['amount'] * 100), // Amount in cents/the smallest currency unit
                'currency' => $data['invoice']->currency_name,
                'description' => 'Invoice Payment #' . $data['invoice']->id,
                'callback_url' => $calurl,
                'success_url' => $calurl,
                'back_url' => $calurl,
                'source' => [
                    'name' => $firstname . ' ' . $lastname,
                    'email' => $email,
                    'company' => $data['invoice']->client->company ?? '',
                    'callback_url' => $calurl
                ],
                'metadata' => [
                    'client_name' => $firstname . ' ' . $lastname,
                    'client_email' => $email,
                    'invoice_id' => $data['invoice']->id,
                    'hash' => $data['invoice']->hash,
                ],
            ];

            // **DEBUGGING:  Dump the payload before sending.**
            log_activity("Payload: " . json_encode($postFields, JSON_PRETTY_PRINT));

            // Make API request to create payment
            $client = new Client();
            $response = $client->request('POST', $apiURL, [
                'body' => json_encode($postFields),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => "Basic " . base64_encode(trim($this->moyasar_secret_key) . ':'),
                ],
            ]);

            $moyasarResponse = json_decode($response->getBody()->getContents());

            // **DEBUGGING: Dump the entire response for inspection**
            log_activity("Response" . json_encode($moyasarResponse, JSON_PRETTY_PRINT));

            // Handle response and redirect to payment URL
            if ($moyasarResponse && isset($moyasarResponse->id)) {

                //Redirect to payment page.
                if(isset($moyasarResponse->url)){
                    redirect($moyasarResponse->url);
                }else{
                    set_alert('danger',_l('moyasar_payment_url_missing'));
                    redirect(site_url('invoice/' . $data['invoice']->id . '/' . $data['invoice']->hash));
                }

            }else{
                $errorMessage = isset($moyasarResponse->message) ? $moyasarResponse->message : _l('payment_failed');
                set_alert('danger',_l($errorMessage));
                redirect(site_url('invoice/' . $data['invoice']->id . '/' . $data['invoice']->hash));
            }
        } catch (\Exception $e) {
            log_activity("Moyasar API Exception (Payment): " . $e->getMessage());  //Log exception messages as well.
            set_alert('danger', _l('payment_gateway_record_payment_fail') . ' <br /> ' . $e->getMessage());
            redirect(site_url('invoice/' . $data['invoice']->id . '/' . $data['invoice']->hash));
        }
    }
}
