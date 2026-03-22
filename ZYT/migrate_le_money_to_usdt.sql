-- =====================================================
-- 合约保证金迁移脚本：所有产品 le_money → USDT le_money
-- 执行前请先备份 fox_member_wallet 表！
-- 生成时间：2026-02-24
-- =====================================================

-- 0. 备份（建议先手动执行）
-- CREATE TABLE fox_member_wallet_bak_20260224 AS SELECT * FROM fox_member_wallet;

-- 1. 把所有用户在各产品的 le_money（折算为 USDT）累加到 USDT 产品 wallet 的 le_money
--    折算规则：USDT 产品本身按 1:1，其余产品用 close 价换算
UPDATE fox_member_wallet AS usdt_w
JOIN (
    SELECT
        w.uid,
        SUM(
            CASE
                WHEN p.base = 1 THEN w.le_money                        -- USDT 本身 1:1
                WHEN p.close > 0 THEN ROUND(w.le_money * p.close, 8)   -- 其他币 × 当前价 = USDT
                ELSE 0
            END
        ) AS total_le_usdt
    FROM fox_member_wallet w
    INNER JOIN fox_product_lists p ON p.id = w.product_id
    WHERE w.le_money > 0
    GROUP BY w.uid
) AS agg ON usdt_w.uid = agg.uid
INNER JOIN fox_product_lists pbase ON pbase.id = usdt_w.product_id AND pbase.base = 1
SET usdt_w.le_money = agg.total_le_usdt;

-- 2. 清零所有非 USDT 产品的 le_money
UPDATE fox_member_wallet w
INNER JOIN fox_product_lists p ON p.id = w.product_id AND p.base = 0
SET w.le_money = 0
WHERE w.le_money > 0;

-- 验证（执行后检查是否有非 USDT 产品仍有 le_money > 0）
-- SELECT w.uid, p.title, w.le_money
-- FROM fox_member_wallet w
-- JOIN fox_product_lists p ON p.id = w.product_id
-- WHERE w.le_money > 0;
