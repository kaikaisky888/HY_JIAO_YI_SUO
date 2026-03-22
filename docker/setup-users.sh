#!/bin/sh
mysql -u root -proot123456 -e "
CREATE USER IF NOT EXISTS 'curve_1'@'%' IDENTIFIED BY 'xff7EBH3RhDFskfb';
GRANT ALL PRIVILEGES ON curve_1.* TO 'curve_1'@'%';
CREATE USER IF NOT EXISTS 'curve_2'@'%' IDENTIFIED BY '2AMzBsa52WGwPkhG';
GRANT ALL PRIVILEGES ON curve_2.* TO 'curve_2'@'%';
FLUSH PRIVILEGES;
"
