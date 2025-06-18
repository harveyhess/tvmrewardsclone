<?php
// Define all application routes
$routes = [
    // Patient routes
    'patient/login.php' => [
        'controller' => 'PatientController',
        'action' => 'login',
        'view' => 'patient/login'
    ],
    'patient/dashboard.php' => [
        'controller' => 'PatientController',
        'action' => 'dashboard',
        'view' => 'patient/dashboard'
    ],
    
    // Admin routes
    'admin/dashboard.php' => [
        'controller' => 'AdminController',
        'action' => 'dashboard',
        'view' => 'admin/dashboard'
    ],
    'admin/patients.php' => [
        'controller' => 'AdminController',
        'action' => 'patients',
        'view' => 'admin/patients'
    ],
    'admin/login.php' => [
        'controller' => 'AdminController',
        'action' => 'login',
        'view' => 'admin/login'
    ]
]; 