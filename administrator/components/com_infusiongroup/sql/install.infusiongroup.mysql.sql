CREATE TABLE IF NOT EXISTS `#__infusiongroup_mappings` (
	`infusiongroup_mapping_id` SERIAL,
	`ix_tag_id` INT(11) NOT NULL,
	`ix_tag_name` VARCHAR(80) NOT NULL,
	`group_id` BIGINT(20),
	PRIMARY KEY (`infusiongroup_mapping_id`)
);