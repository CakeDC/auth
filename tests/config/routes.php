<?php
\Cake\Routing\Router::connect('/my-test', [
    'plugin' => 'CakeDC/Users',
    'controller' => 'Users',
    'action' => 'myTest',
]);
