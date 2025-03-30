<?php

/**
 * Ensures that the module init file can't be accessed directly, only within the application.
 */
defined('BASEPATH') or exit('No direct script access allowed');
/*
Module Name: Moyasar Gateway
Description: The easiest way to accept payments for businesses in Middle east using Moyasar. ( https://www.moyasar.com )
Author: amolood
Author URI: https://www.amolood.com
Version: 1.0.0
Requires at least: 2.3.*
*/
register_payment_gateway('Moyasar_gateway', 'moyasar');
