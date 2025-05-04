CREATE TABLE `Product` (
  `id` int PRIMARY KEY,
  `name` varchar(255),
  `description` varchar(255),
  `price` float,
  `type` int
);

CREATE TABLE `ComposedBy` (
  `product_id` int,
  `child_id` int,
  PRIMARY KEY (`product_id`, `child_id`)
);

CREATE TABLE `PromotionalProduct` (
  `id` int PRIMARY KEY,
  `code` varchar(255) UNIQUE,
  `points` int
);

CREATE TABLE `Order` (
  `id` int PRIMARY KEY,
  `date` date,
  `time` timestamp,
  `table` varchar(255),
  `user` int
);

CREATE TABLE `OrderContains` (
  `order` int,
  `product` int,
  `price` float,
  PRIMARY KEY (`order`, `product`)
);

CREATE TABLE `User` (
  `id` int PRIMARY KEY,
  `email` varchar(255),
  `name` varchar(255),
  `password` varchar(255),
  `points` int
);

CREATE TABLE `Table` (
  `id` varchar(255) PRIMARY KEY,
  `notes` varchar(255)
);

ALTER TABLE `Order` ADD FOREIGN KEY (`table`) REFERENCES `Table` (`id`);

ALTER TABLE `Order` ADD FOREIGN KEY (`user`) REFERENCES `User` (`id`);

ALTER TABLE `Product` ADD FOREIGN KEY (`id`) REFERENCES `ComposedBy` (`product_id`);

ALTER TABLE `Product` ADD FOREIGN KEY (`id`) REFERENCES `ComposedBy` (`child_id`);

ALTER TABLE `Product` ADD FOREIGN KEY (`id`) REFERENCES `PromotionalProduct` (`id`);

ALTER TABLE `OrderContains` ADD FOREIGN KEY (`product`) REFERENCES `Product` (`id`);

ALTER TABLE `Order` ADD FOREIGN KEY (`id`) REFERENCES `OrderContains` (`order`);
