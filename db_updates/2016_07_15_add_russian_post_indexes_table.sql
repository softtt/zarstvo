CREATE TABLE `ps_russian_post_indexes` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `index` VARCHAR(45) NOT NULL,
  `opsname` VARCHAR(255) NULL,
  `region` VARCHAR(255) NULL,
  `autonom` VARCHAR(255) NULL,
  `area` VARCHAR(255) NULL,
  `city` VARCHAR(255) NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `id_UNIQUE` (`id` ASC),
  UNIQUE INDEX `index_UNIQUE` (`index` ASC))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;