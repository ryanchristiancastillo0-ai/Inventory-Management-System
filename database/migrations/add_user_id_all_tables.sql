-- ============================================================
-- ADD USER_ID TO PRODUCTS, ORDER_ITEMS, STOCK_MOVEMENTS
-- Paste this into phpMyAdmin > SQL tab and click GO
-- Your DB: inventory_db
-- ============================================================

USE inventory_db;

-- 1) PRODUCTS: add user_id
ALTER TABLE `products`
  ADD COLUMN `user_id` int UNSIGNED DEFAULT NULL AFTER `id`,
  ADD KEY `idx_products_user` (`user_id`),
  ADD CONSTRAINT `fk_products_user`
      FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
      ON DELETE CASCADE;

-- 2) ORDER_ITEMS: add user_id (copied from parent order)
ALTER TABLE `order_items`
  ADD COLUMN `user_id` int UNSIGNED DEFAULT NULL AFTER `id`,
  ADD KEY `idx_order_items_user` (`user_id`),
  ADD CONSTRAINT `fk_order_items_user`
      FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
      ON DELETE CASCADE;

-- 3) STOCK_MOVEMENTS: add user_id
ALTER TABLE `stock_movements`
  ADD COLUMN `user_id` int UNSIGNED DEFAULT NULL AFTER `id`,
  ADD KEY `idx_stock_movements_user` (`user_id`),
  ADD CONSTRAINT `fk_stock_movements_user`
      FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
      ON DELETE CASCADE;

-- ============================================================
-- ASSIGN EXISTING ROWS TO YOUR FIRST USER (ryan@gmail.com)
-- If ryan is NOT user id 1, change the '1' below!
-- ============================================================

UPDATE `products`         SET `user_id` = 1 WHERE `user_id` IS NULL;
UPDATE `stock_movements`  SET `user_id` = 1 WHERE `user_id` IS NULL;

UPDATE `order_items` oi
JOIN `orders` o ON oi.order_id = o.id
SET oi.user_id = o.user_id
WHERE oi.user_id IS NULL;
-- ============================================================
