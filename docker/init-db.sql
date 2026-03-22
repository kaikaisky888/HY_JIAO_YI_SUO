-- 创建 curve_2 数据库和用户
CREATE DATABASE IF NOT EXISTS `curve_2` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
CREATE USER IF NOT EXISTS 'curve_2'@'%' IDENTIFIED BY '2AMzBsa52WGwPkhG';
GRANT ALL PRIVILEGES ON `curve_2`.* TO 'curve_2'@'%';

-- 同时给 curve_1 用户远程访问权限
GRANT ALL PRIVILEGES ON `curve_1`.* TO 'curve_1'@'%';

FLUSH PRIVILEGES;

-- 切换到 curve_1 数据库（为后续 curve_1.sql 准备）
USE `curve_1`;
