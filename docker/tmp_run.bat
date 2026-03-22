@echo off
fly ssh console --app kvi-mysql -C "sh -c 'echo CREATE USER IF NOT EXISTS curve_1 IDENTIFIED BY 0x78666637454248335268444673006662 | mysql -uroot -proot123456'"
