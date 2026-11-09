-- Migration: Add user_id to stock_movements table
-- Date: 2026-11-09
-- Description: Add user_id column to track which user performed each stock movement

USE inventory_db;

-- Add user_id column to stock_movements table
ALTER TABLE `stock_movements`
ADD COLUMN `user_id` int UNSIGNED DEFAULT NULL AFTER `id`,
ADD CONSTRAINT `fk_stock_movements_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`)
    ON DELETE CASCADE;

-- Add index for better query performance
ALTER TABLE `stock_movements`
ADD KEY `idx_stock_movements_user` (`user_id`);

-- Update existing stock_movements to be owned by user_id=1 (or the appropriate admin user)
UPDATE `stock_movements` SET `user_id` = 1 WHERE `user_id` IS NULL;