<?php
// The setup wizard creates config/config.php for you. This sample only shows the shape.
return [
    'db' => ['host' => 'localhost', 'port' => 3306, 'name' => 'DATABASE', 'user' => 'USER', 'pass' => 'PASSWORD'],
    'base_url' => '',            // optional, e.g. https://shop.example.pk (used in emails); empty = detect
    'timezone' => 'Asia/Karachi',
    'debug' => false,            // true only on a development machine
    'mail_enabled' => true,      // uses PHP mail(); set false to only log emails in the notifications table
];
