-- Migration: Add user_id to order_items table
-- Date: 2026-11-09
-- Description: Add user_id column to track which user owns each order item (inherited from order)

USE inventory_db;

-- Add user_id column to order_items table
ALTER TABLE `order_items`
ADD COLUMN `user_id` int UNSIGNED DEFAULT NULL AFTER `id`,
ADD CONSTRAINT `fk_order_items_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`)
    ON DELETE CASCADE;

-- Add index for better query performance
ALTER TABLE `order_items`
ADD KEY `idx_order_items_user` (`user_id`);

-- Update existing order_items to inherit user_id from their parent order
UPDATE `order_items` oi
JOIN `orders` o ON oi.order_id = o.id
SET oi.user_id = o.user_id
WHERE oi.user_id IS NULL;