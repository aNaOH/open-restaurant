CREATE TABLE `Category` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(255) NOT NULL
);

CREATE TABLE `User` (
  `id` int PRIMARY KEY AUTO_INCREMENT NOT NULL,
  `email` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` int NOT NULL,
  `points` int NOT NULL DEFAULT 0
);

CREATE TABLE `Table` (
  `id` varchar(255) PRIMARY KEY NOT NULL,
  `notes` varchar(255) DEFAULT NULL
);

CREATE TABLE `Product` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `price` float,
  `type` int NOT NULL,
  `category` int DEFAULT NULL,
  `code` varchar(255) UNIQUE DEFAULT NULL,
  `points` int DEFAULT NULL,
  FOREIGN KEY (`category`) REFERENCES `Category` (`id`) ON DELETE SET NULL
);

CREATE TABLE `ComposedBy` (
  `product_id` int NOT NULL,
  `child_id` int NOT NULL,
  `position` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`product_id`, `child_id`, `position`),
  FOREIGN KEY (`product_id`) REFERENCES `Product` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`child_id`) REFERENCES `Product` (`id`) ON DELETE CASCADE
);

CREATE TABLE `ComposedCategory` (
  `product_id` int NOT NULL,
  `category_id` int NOT NULL,
  `position` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`product_id`, `category_id`, `position`),
  FOREIGN KEY (`product_id`) REFERENCES `Product` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `Category` (`id`) ON DELETE CASCADE
);

CREATE TABLE `Orders` (
  `id` int PRIMARY KEY AUTO_INCREMENT NOT NULL,
  `date` date NOT NULL,
  `time` timestamp NOT NULL,
  `table_id` varchar(255) NOT NULL,
  `user` int DEFAULT NULL,
  `stripe_id` varchar(255) DEFAULT NULL,
  FOREIGN KEY (`table_id`) REFERENCES `Table` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user`) REFERENCES `User` (`id`) ON DELETE SET NULL
);

CREATE TABLE `OrderContains` (
  `order_id` int NOT NULL,
  `product` int NOT NULL,
  `price` float NOT NULL,
  `done` boolean NOT NULL DEFAULT false,
  `quantity` int NOT NULL DEFAULT 1,
  `metadata` text DEFAULT NULL,
  PRIMARY KEY (`order_id`, `product`),
  FOREIGN KEY (`order_id`) REFERENCES `Orders` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product`) REFERENCES `Product` (`id`) ON DELETE CASCADE
);