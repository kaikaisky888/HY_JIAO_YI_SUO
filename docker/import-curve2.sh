#!/bin/bash
# 导入 curve_2.sql 到 curve_2 数据库
mysql -uroot -p"$MYSQL_ROOT_PASSWORD" curve_2 < /sql-import/curve_2.sql
