<?php

return $config['dbConnections'] = array(
    'development' => [
        'info' => [
            'username'  => 'root',
            'password'  => '',
            'dsn'       => '(description=(address=(protocol=tcp)(host=localhost)(port=1521))(connect_data=(sid=xe)))'
        ]
    ]
);