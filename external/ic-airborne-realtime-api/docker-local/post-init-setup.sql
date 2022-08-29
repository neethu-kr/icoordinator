SET FOREIGN_KEY_CHECKS=0;
create database if not exists bimcontact;
use bimcontact;
INSERT INTO `license_chargify_mappers` (`id`, `license_id`, `chargify_website_id`, `chargify_product_handle`, `chargify_users_component_ids`, `chargify_workspaces_component_ids`, `chargify_storage_component_ids`)
VALUES
	(1,1,'ic_eur','basic-*','123|345',NULL,NULL);

INSERT INTO `licenses` (`id`, `users_limit`, `workspaces_limit`, `storage_limit`, `file_size_limit`)
VALUES
	(1,3,5,100,5);

INSERT INTO `oauth_clients` (`client_id`, `client_secret`, `redirect_uri`, `grant_types`, `scope`, `user_id`)
VALUES
  ('icoordinator_web',NULL,NULL,NULL,NULL,NULL),
	('icoordinator_desktop',NULL,NULL,NULL,NULL,NULL);

INSERT INTO `subscriptions` (`id`, `portal_id`, `license_id`, `users_allocation`, `workspaces_allocation`, `storage_allocation`, `state`)
VALUES
	(1,1,1,NULL,NULL,NULL,'active');
INSERT INTO `portals` (`id`, `created_at`, `modified_at`, `owned_by`, `name`, `uuid`)
VALUES
	(1,NOW(),NOW(),1,'Portal1',NULL),
	(2,NOW(),NOW(),1,'Portal2',NULL);

INSERT INTO `workspaces` (`id`, `name`, `created_at`, `modified_at`, `is_deleted`, `portal_id`, `desktop_sync`)
VALUES
	(1,'Workspace1',NOW(),NOW(),0,1,1),
	(2,'Workspace2',NOW(),NOW(),0,2,1);

INSERT INTO `users` (`id`, `email`, `password`, `name`, `job_title`, `phone`, `address`, `avatar_url`, `created_at`, `modified_at`, `is_deleted`, `uuid`, `email_confirmed`, `instant_notification`)
VALUES
	(1,'test@user.com','$2y$10$f2FUOo5jcvikH.yQ5AotbeO0c5gExiJDTu3bX6.y6k7A9bmQmnsce','Test User',NULL,NULL,NULL,NULL,NOW(),NOW(),0,'d3fc00cf-e97c-445f-a005-1258a9751a2f',1,0),
	(2,'test2@user.com','$2y$10$f2FUOo5jcvikH.yQ5AotbeO0c5gExiJDTu3bX6.y6k7A9bmQmnsce','Test User2',NULL,NULL,NULL,NULL,NOW(),NOW(),0,'7ab22bf9-f158-4af0-9847-f22346c8cea4',1,0);

INSERT INTO `user_locales` (`id`, `user_id`, `lang`, `date_format`, `time_format`, `first_week_day`)
VALUES
	(1,1,'en','dd/mm/yyyy','HH:MM',1),
	(2,2,'en','dd/mm/yyyy','HH:MM',1);

INSERT INTO `acl_resources` (`id`, `entity_id`, `created_at`, `modified_at`, `entity_type`)
VALUES
	(1,1,NOW(),NOW(),'portal'),
	(2,2,NOW(),NOW(),'portal'),
	(3,1,NOW(),NOW(),'workspace'),
	(4,2,NOW(),NOW(),'workspace');

INSERT INTO `acl_roles` (`id`, `entity_id`, `created_at`, `modified_at`, `entity_type`)
VALUES
	(1,1,NOW(),NOW(),'user'),
	(2,2,NOW(),NOW(),'user');

INSERT INTO `acl_permissions` (`id`, `portal_id`, `acl_role_id`, `acl_resource_id`, `granted_by`, `bit_mask`, `created_at`, `modified_at`, `is_deleted`)
VALUES
	(1,1,1,1,NULL,1,NOW(),NOW(),0),
	(2,1,1,2,NULL,2,NOW(),NOW(),0),
	(3,1,1,3,NULL,1,NOW(),NOW(),0),
	(4,2,1,4,NULL,1,NOW(),NOW(),0),
	(5,1,2,1,NULL,1,NOW(),NOW(),0),
	(6,1,2,2,NULL,2,NOW(),NOW(),0),
	(7,1,2,3,NULL,1,NOW(),NOW(),0),
	(8,2,2,4,NULL,1,NOW(),NOW(),0);

INSERT INTO `events` (`id`, `created_by`, `source_id`, `created_at`, `source_type`, `type`, `portal_id`, `user_id`)
VALUES
	(1,1,1,'2019-09-11 12:18:24','portal','PORTAL_CREATE',NULL,1),
	(2,1,1,'2019-09-11 12:18:24','portal','PORTAL_CREATE',NULL,2);

SET FOREIGN_KEY_CHECKS=1;