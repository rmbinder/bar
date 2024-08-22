# bar

(b)ackup (a)nd (r)estore

bar is a program for Admidio, but not a classic plugin. It backs up and restores an Admidio SQL database and its associated web space.

## Installation

bar must be in a subfolder of root (on the same level as e.g. adm_plugins). The folder name can be freely selected.

## Usage:

The program is called via the command line with these parameters:

mode=show or mode=backup or mode=restore

source=sql or source=web or source=sql_web or source=admidio

Example:

...bar.php?mode=restore&source=sql

## Backup files

The created backup files are saved in the folder of BAR.
By default these are backup_db.sql and backup_web.zip.

