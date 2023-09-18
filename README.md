# Mass WordPress Databases Exporter
WordPress Databases Exporter

This is a command line tool that scans local folders for WordPress sites and exports the database into site's folder as .ht_site_db.sql file.

# Usage
wp_db_exporter.php /var/www/sites

As a WordPress developer you have lots of WordPress sites installed on your computer, you live or staging servers. 
Every WordPress site needs a folder to store its files and also a database to connect to to get and put contents to. 
For this reason it's always good to keep the files nicely organized to have the database exported inside the site’s root directory or one level above it. That way when you backup the site using your favorite archiving tool (zip, tar, 7zip) you know that all that is necessary is packaged.

If you find bugs or have a suggestion, create a ticket using the link below
https://github.com/orbisius/wp_db_exporter/issues/issues

# Requirements
- php
- WP-CLI to be installed
- exec() functions to be available

# Troubleshooting
Please, check your apache and WordPress debug.log files.

# Hire us
If you'd us to customize the tool or troubleshoot your site (both paid services) feel free to get a quote
https://orbisius.com/contact

# Here are the services we offer
https://orbisius.com/services

Author: Svetoslav Marinov (Slavi) | http://orbisius.com

# Changelog

1.0.0 - initial release
