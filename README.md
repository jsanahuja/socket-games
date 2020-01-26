# socket-game

https://games.sowecms.com/play/parchis/kraken

## Daemonds

```
[Unit]
Description=Parchis
After=network.target

[Service]
WorkingDirectory=/opt/www/games/servers
User=root
Group=root
Type=forking
ExecStart=/opt/www/games/servers/parchis.php start -d 2>&1 >> /opt/www/games/logs/debug.log
ExecStop=/opt/www/games/servers/parchis.php stop -g
ExecReload=/opt/www/games/servers/parchis.php reload -g
RestartSec=15
Restart=always

[Install]
WantedBy=multi-user.target
```