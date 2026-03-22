-- =============================================
-- 升级脚本 v2 - 多链支持 + 高级认证 + 拒绝原因显示
-- 执行前请备份数据库
-- =============================================

-- 1. fox_product_lists 表：添加 BSC、POL、Base 链地址字段
ALTER TABLE `fox_product_lists` 
ADD COLUMN `bsc_address` varchar(255) NOT NULL DEFAULT '' COMMENT 'BSC链充值地址' AFTER `pay_address`,
ADD COLUMN `pol_address` varchar(255) NOT NULL DEFAULT '' COMMENT 'POL链充值地址' AFTER `bsc_address`,
ADD COLUMN `base_address` varchar(255) NOT NULL DEFAULT '' COMMENT 'Base链充值地址' AFTER `pol_address`;

-- 2. fox_product_lists 表：添加 BSC、POL、Base 链提币手续费字段
ALTER TABLE `fox_product_lists` 
ADD COLUMN `withdraw_bsc_sxf` decimal(20,4) NOT NULL DEFAULT 0.0000 COMMENT 'BSC链提币手续费' AFTER `withdraw_omni_sxf`,
ADD COLUMN `withdraw_pol_sxf` decimal(20,4) NOT NULL DEFAULT 0.0000 COMMENT 'POL链提币手续费' AFTER `withdraw_bsc_sxf`,
ADD COLUMN `withdraw_base_sxf` decimal(20,4) NOT NULL DEFAULT 0.0000 COMMENT 'Base链提币手续费' AFTER `withdraw_pol_sxf`;

-- 3. fox_member_wallet 表：添加 BSC、POL、Base 链提币地址字段
ALTER TABLE `fox_member_wallet` 
ADD COLUMN `withdraw_bsc_address` varchar(255) NOT NULL DEFAULT '' COMMENT 'BSC链提币地址' AFTER `withdraw_omni_address`,
ADD COLUMN `withdraw_pol_address` varchar(255) NOT NULL DEFAULT '' COMMENT 'POL链提币地址' AFTER `withdraw_bsc_address`,
ADD COLUMN `withdraw_base_address` varchar(255) NOT NULL DEFAULT '' COMMENT 'Base链提币地址' AFTER `withdraw_pol_address`;

-- 4. fox_product_lists 添加排序权重字段（用于按流行度排序）
ALTER TABLE `fox_product_lists` 
ADD COLUMN `popularity_sort` int(11) NOT NULL DEFAULT 0 COMMENT '流行度排序(越大越靠前)' AFTER `sort`;

-- 5. 设置主流币种的流行度排序
UPDATE `fox_product_lists` SET `popularity_sort` = 100 WHERE `title` = 'BTC';
UPDATE `fox_product_lists` SET `popularity_sort` = 90 WHERE `title` = 'ETH';
UPDATE `fox_product_lists` SET `popularity_sort` = 85 WHERE `title` = 'USDT';
UPDATE `fox_product_lists` SET `popularity_sort` = 80 WHERE `title` = 'USDC';
UPDATE `fox_product_lists` SET `popularity_sort` = 75 WHERE `title` = 'SOL';
UPDATE `fox_product_lists` SET `popularity_sort` = 70 WHERE `title` = 'BNB';
UPDATE `fox_product_lists` SET `popularity_sort` = 65 WHERE `title` = 'XRP';
UPDATE `fox_product_lists` SET `popularity_sort` = 60 WHERE `title` = 'DOGE';
UPDATE `fox_product_lists` SET `popularity_sort` = 55 WHERE `title` = 'ADA';
UPDATE `fox_product_lists` SET `popularity_sort` = 50 WHERE `title` = 'DOT';
UPDATE `fox_product_lists` SET `popularity_sort` = 45 WHERE `title` = 'LINK';
UPDATE `fox_product_lists` SET `popularity_sort` = 40 WHERE `title` = 'LTC';
UPDATE `fox_product_lists` SET `popularity_sort` = 35 WHERE `title` = 'BCH';
UPDATE `fox_product_lists` SET `popularity_sort` = 30 WHERE `title` = 'FIL';
UPDATE `fox_product_lists` SET `popularity_sort` = 25 WHERE `title` = 'ETC';
UPDATE `fox_product_lists` SET `popularity_sort` = 20 WHERE `title` = 'SHIB';

-- 6. fox_member_card 表：添加高级认证字段
ALTER TABLE `fox_member_card` 
ADD COLUMN `advanced_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '高级认证状态 0未提交 1待审核 2已通过 3已拒绝' AFTER `status`,
ADD COLUMN `advanced_video` varchar(500) NOT NULL DEFAULT '' COMMENT '高级认证视频路径' AFTER `card_c`,
ADD COLUMN `advanced_remark` varchar(500) NOT NULL DEFAULT '' COMMENT '高级认证备注/拒绝原因' AFTER `advanced_video`,
ADD COLUMN `advanced_time` int(11) NOT NULL DEFAULT 0 COMMENT '高级认证提交时间' AFTER `advanced_remark`,
ADD COLUMN `advanced_do_time` int(11) NOT NULL DEFAULT 0 COMMENT '高级认证审核时间' AFTER `advanced_time`;

-- 7. 更新 usdt_recharge_type 配置 - 需要同步更新 allset.php 配置文件
-- type映射: 1=Omni, 2=TRC20, 3=ERC20, 4=OTHER(弃用), 5=BSC, 6=POL, 7=Base
