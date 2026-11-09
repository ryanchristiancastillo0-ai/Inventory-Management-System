-- Migration: Add user_id to products table
-- Date: 2026-11-09
-- Description: Add user_id column to track which user owns each product

USE inventory_db;

-- Add user_id column to products table
ALTER TABLE `products`
ADD COLUMN `user_id` int UNSIGNED DEFAULT NULL AFTER `id`,
ADD CONSTRAINT `fk_products_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`)
    ON DELETE SET NULL;

-- Add index for better query performance
ALTER TABLE `products`
ADD KEY `idx_products_user` (`user_id`);

-- Update existing products to be owned by user_id=1 (or the appropriate admin user)
UPDATE `products` SET `user_id` = 1 WHERE `user_id` IS NULL;