<?php

$config = array(
    'announceUri' => 'http://localhost:80/torrent/announce.php',
    'filesDir' => '/var/www/ocs-fileserver/data/files',
    'torrentsDir' => '/var/www/ocs-fileserver/data/torrents',
    'db' => array(
        'db_host' => 'localhost',
        'db_user' => 'username',
        'db_password' => 'password',
        'db_name' => 'ocsfileserver'
    ),
    'seeder' => array(
        'seeder_address' => '127.0.0.1',
        'seeder_internal_address' => '0.0.0.0',
        'seeder_port' => 49153,
        'peer_forks' => 100,
        'seeders_stop_seeding' => 200
    )
);
