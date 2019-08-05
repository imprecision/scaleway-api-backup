# scaleway-api-backup

A simple PHP command-line based tool to automate backups of Scaleway instances (snapshots or images). Can be run manually or automatically e.g. via crontab.

## How to run:

### Define config:

    src/config/backup.config.php

### Deployment

Put on your server as you please.

### Execute manually:

    php ./cli-api-scaleway-backups.php

### Schedule with crontab:

e.g. Run at 00:10 each day:

    0 10 * * * /usr/bin/php /my-uploaded-location/cli-api-scaleway-backups.php > /tmp/cron-scaleway.log 2>&1
