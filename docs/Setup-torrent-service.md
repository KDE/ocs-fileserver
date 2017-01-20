# Setup torrent service

Setup information for torrent generator, tracker and seed server.

Find in: {project}/public/torrent


## Database

Create database tables.

You can use "phptracker-mysql.sql" to create database tables.


## Config for PHPTracker

Make "config.php" from "config.sample.php".


## Seed server: rtorrent (Recommended)

Open server port.

    $ sudo iptables -I INPUT -p tcp -m tcp --dport 49152:65535 -j ACCEPT
    $ sudo iptables -I INPUT -p udp -m udp --dport 49152:65535 -j ACCEPT
    $ sudo iptables -I OUTPUT -p tcp -m tcp --dport 49152:65535 -j ACCEPT
    $ sudo iptables -I OUTPUT -p udp -m udp --dport 49152:65535 -j ACCEPT

    [ Note ]

    Delete the rule:
    $ sudo iptables -D INPUT -p tcp -m tcp --dport 49152:65535 -j ACCEPT
    $ sudo iptables -D INPUT -p udp -m udp --dport 49152:65535 -j ACCEPT
    $ sudo iptables -D OUTPUT -p tcp -m tcp --dport 49152:65535 -j ACCEPT
    $ sudo iptables -D OUTPUT -p udp -m udp --dport 49152:65535 -j ACCEPT

    Save iptables rules:
    $ sudo iptables-save > filename

    Restore iptables rules:
    $ sudo iptables-restore < filename

Make "/home/username/.rtorrent.rc" from "rtorrent.sample.rc".

Make session directory.

    $ mkdir /home/username/.rtorrent

Launch rtorrent inside tmux.

    $ tmux

    [ tmux session ]
    $ rtorrent
    C-b d (Detach the tmux session)


## Seed server: transmission-daemon (Alternative)

Open server port.

    $ sudo iptables -I INPUT -p tcp -m tcp --dport 49152:65535 -j ACCEPT
    $ sudo iptables -I INPUT -p udp -m udp --dport 49152:65535 -j ACCEPT
    $ sudo iptables -I OUTPUT -p tcp -m tcp --dport 49152:65535 -j ACCEPT
    $ sudo iptables -I OUTPUT -p udp -m udp --dport 49152:65535 -j ACCEPT

    [ Note ]

    Delete the rule:
    $ sudo iptables -D INPUT -p tcp -m tcp --dport 49152:65535 -j ACCEPT
    $ sudo iptables -D INPUT -p udp -m udp --dport 49152:65535 -j ACCEPT
    $ sudo iptables -D OUTPUT -p tcp -m tcp --dport 49152:65535 -j ACCEPT
    $ sudo iptables -D OUTPUT -p udp -m udp --dport 49152:65535 -j ACCEPT

    Save iptables rules:
    $ sudo iptables-save > filename

    Restore iptables rules:
    $ sudo iptables-restore < filename

Stop transmission-daemon.

    $ sudo service transmission-daemon stop

Change settings in /etc/transmission-daemon/settings.json.

You can use "transmission-settings.sample.json" to replace the "settings.json".

Start transmission-daemon.

    $ sudo service transmission-daemon start


## Seed server: seeder-cli.php (Alternative)

Open server port.

    $ sudo iptables -I INPUT -p tcp -m tcp --dport 49153 -j ACCEPT
    $ sudo iptables -I OUTPUT -p tcp -m tcp --dport 49153 -j ACCEPT

Make log file.

    $ sudo touch /var/log/phptracker.log
    $ sudo chown www-data:www-data /var/log/phptracker.log

Launch seeder-cli.php.

    $ sudo php seeder-cli.php
