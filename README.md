# Perfex CRM - Moyasar Payment Gateway

## 📚 Overview

This module integrates **Moyasar Payment Gateway** with **Perfex CRM**, enabling businesses to seamlessly accept payments through Moyasar. It offers secure payment processing and real-time transaction updates to enhance client payment experiences.

---

## ⚡️ Features

✅ Seamless integration with Perfex CRM.  
✅ Supports payments via Credit Cards, Apple Pay, and STC Pay.  
✅ Real-time transaction verification and status updates.  
✅ Secure API communication using Moyasar’s REST API.  
✅ Configurable secret keys for test and live environments.  

---

## 🛠️ Installation Instructions

### 1. Upload the Module
- Download and Upload the zip `moyasar` folder from the admin panel -> Settings -> Modules -> install



### 2. Activate the Module
- Go to **Perfex CRM Admin Panel**.
- Navigate to:
Setup -> Modules -> Moyasar


- Click **Activate**.

### 3. Configure Moyasar API Keys
- Go to:
Setup -> Settings -> Payment Gateways -> Moyasar


- Add the following credentials:
    - ✅ `Moyasar Secret Key`
    - ✅ Currencies (Default: SAR)

---

## 🔥 Usage

### To Enable Moyasar for Invoice Payments:
1. Navigate to:
Invoices -> Invoice Settings

2. Enable **Moyasar** as a payment method.

---

## ⚡️ API Configuration

Make sure to use the correct API keys:

- **Test Secret Key**: For development and sandbox environments.
- **Live Secret Key**: For production.

---

## ⚡️ Dynamic API Key Setup

You can dynamically set the `moyasar_secret_key` by adding it via the gateway settings:
```php
$this->setSettings(
    [
        [
            'name'      => 'moyasar_secret_key',
            'encrypted' => false,
            'label'     => 'Secret Key',
        ],
        [
            'name'             => 'currencies',
            'label'            => 'settings_paymentmethod_currencies',
            'default_value'    => 'SAR',
        ]
    ]
);
```

To retrieve the secret key dynamically:

```php
$moyasar_secret_key = $this->getSetting('moyasar_secret_key');
```

📄 Changelog
v1.0.0
Initial release of the Moyasar integration.
Supports payment collection, verification, and secure API handling.

❗️ Support & Assistance
For assistance or to report any issues, please contact us at:

📧 support@amolood.com

🌐 https://www.amolood.com

📜 License
