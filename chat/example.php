<?php

require 'functionDB.php'; // Adjust the path as necessary

use xvonsteam\functionDB;

// Example usage

// 1. Register a new user
$registerInstance = new functionDB('register', 'user123', 'user@example.com', 'securepassword');
echo $registerInstance->performAction() . PHP_EOL;

// 2. Login with the registered user
$loginInstance = new functionDB('login', 'user123', null, 'securepassword');
echo $loginInstance->performAction() . PHP_EOL;

// 3. Create a resource for the user
$createInstance = new functionDB('create', 'user123');
echo $createInstance->performAction() . PHP_EOL;

// 4. View resources for the user
$viewInstance = new functionDB('view', 'user123');
echo $viewInstance->performAction() . PHP_EOL;

// 5. Update a resource for the user
$updateInstance = new functionDB('update', 'user123');
echo $updateInstance->performAction() . PHP_EOL;

// 6. Delete a resource for the user
$deleteInstance = new functionDB('delete', 'user123');
echo $deleteInstance->performAction() . PHP_EOL;

// 7. Simulate a GET request to retrieve a specific resource
$getInstance = new functionDB('GET', 'user123');
$_GET['id'] = 1; // Simulate getting ID from URL
echo $getInstance->performAction() . PHP_EOL;

// 8. Simulate a POST request to register a new user
$postInstance = new functionDB('POST', 'user456', 'user456@example.com', 'anotherpassword');
echo $postInstance->performAction() . PHP_EOL;
