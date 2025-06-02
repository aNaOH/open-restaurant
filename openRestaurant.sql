CREATE TABLE `Category` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(255) NOT NULL
);

CREATE TABLE `User` (
  `id` int PRIMARY KEY AUTO_INCREMENT NOT NULL,
  `email` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
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
  FOREIGN KEY (`category`) REFERENCES `Category` (`id`)
);

CREATE TABLE `ComposedBy` (
  `product_id` int NOT NULL,
  `child_id` int NOT NULL,
  `position` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`product_id`, `child_id`),
  FOREIGN KEY (`product_id`) REFERENCES `Product` (`id`),
  FOREIGN KEY (`child_id`) REFERENCES `Product` (`id`)
);

CREATE TABLE `ComposedCategory` (
  `product_id` int NOT NULL,
  `category_id` int NOT NULL,
  `position` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`product_id`, `category_id`),
  FOREIGN KEY (`product_id`) REFERENCES `Product` (`id`),
  FOREIGN KEY (`category_id`) REFERENCES `Category` (`id`)
);

CREATE TABLE `Order` (
  `id` int PRIMARY KEY AUTO_INCREMENT NOT NULL,
  `date` date NOT NULL,
  `time` timestamp NOT NULL,
  `table` varchar(255) NOT NULL,
  `user` int DEFAULT NULL,
  FOREIGN KEY (`table`) REFERENCES `Table` (`id`),
  FOREIGN KEY (`user`) REFERENCES `User` (`id`)
);

CREATE TABLE `OrderContains` (
  `order` int NOT NULL,
  `product` int NOT NULL,
  `price` float NOT NULL,
  PRIMARY KEY (`order`, `product`),
  FOREIGN KEY (`order`) REFERENCES `Order` (`id`),
  FOREIGN KEY (`product`) REFERENCES `Product` (`id`)
);