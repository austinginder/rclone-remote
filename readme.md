<h1>Rclone Remote</h1>

[Rclone Remote](http://github.com/austinginder/rclone-remote/) is a WordPress plugin that turns WordPress into a Rclone HTTP remote. This was created as an experiment. Not intended for production use.

[![emoji-log](https://cdn.rawgit.com/ahmadawais/stuff/ca97874/emoji-log/flat.svg)](https://github.com/ahmadawais/Emoji-Log/)

## **Warning**
This was created as an experiment and **should not** be used on public WordPress sites.

## Installation

1. Upload `/rclone-remote/` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Enable Rclone remote from `/wp-admin/options-general.php?page=rclone_remote` and follow listed usage instructions.

## Known Issues

1. Rclone HTTP remote doesn't fully support basic auth so the remote file listings are publicly exposed. Please disable when sync is done other wise others could download a copy of your site with the random token.
2. Rclone HTTP remote doesn't support streaming files from a script url like `/rclone/?file=wp-config.php`. Most web server will serve various file extentions directly which would bypass this PHP script. As a workaround the PHP script will serve all files as .rclone-serve.html which allows files to be delivered via Rclone. This requires some hacky bash cleanup as noted in usage instructions.
3. Rclone attempts to use many concurrent checkers and transfers. This is likely to result in `HTTP Error 429: 429 Too Many Requests` with most web hosts. As a workaround try using `--transfers 2 --checkers 2`.

## License
See `LICENSE.txt`