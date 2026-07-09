-- Rebrand Sangvish -> Viberide (idempotent; safe to run multiple times)
-- Local:  mysql -h 127.0.0.1 -P 3308 -u viverider_usr -p viverider < deploy/rebrand-viberide.sql
-- Prod:   docker exec -i viverider-mysql mysql -h 127.0.0.1 -u viverider_usr -p"$DB_PASSWORD" viverider < deploy/rebrand-viberide.sql

UPDATE settings SET value = 'viberidegh' WHERE name = 'app_name';
UPDATE settings SET value = '2026 © viberidegh.' WHERE name = 'footer_content1';
UPDATE settings SET value = 'Design & Develop by viberidegh' WHERE name = 'footer_content2';
UPDATE settings SET value = REPLACE(REPLACE(REPLACE(value, 'Sangvish', 'viberidegh'), 'sangvish', 'viberidegh'), 'Viberide', 'viberidegh')
WHERE name IN ('footer_content1', 'footer_content2', 'mail_from_name');
UPDATE settings SET value = 'support@viberidegh.online' WHERE name IN ('mail_username', 'mail_from_address') AND value LIKE '%sangvish%';

UPDATE third_party_settings SET value = REPLACE(REPLACE(value, '@sangvish.com', '@viberidegh.online'), 'sangvish', 'viberidegh') WHERE value LIKE '%sangvish%';

UPDATE landing_contacts SET
  contact_mail = REPLACE(REPLACE(contact_mail, 'dilip@sangvish.com', 'support@viberidegh.online'), 'support@sangvish.com', 'support@viberidegh.online'),
  contact_web = REPLACE(contact_web, 'https://sangvish.in/', 'https://viberidegh.online/');

UPDATE landing_headers SET
  user_play_link = REPLACE(user_play_link, 'https://taxi1.sangvish.com/', 'https://viberidegh.online/'),
  user_apple_link = REPLACE(user_apple_link, 'https://taxi1.sangvish.com/', 'https://viberidegh.online/'),
  driver_play_link = REPLACE(driver_play_link, 'https://taxi1.sangvish.com/', 'https://viberidegh.online/'),
  driver_apple_link = REPLACE(driver_apple_link, 'https://taxi1.sangvish.com/', 'https://viberidegh.online/'),
  copy_rights = REPLACE(REPLACE(REPLACE(REPLACE(copy_rights, 'sangvish', 'viberidegh'), 'Viberide', 'viberidegh'), ' - Alternativo', ''), '2021 @', '2026 @');

UPDATE landing_homes SET
  hero_user_link_android = REPLACE(hero_user_link_android, 'https://taxi1.sangvish.com/', 'https://viberidegh.online/'),
  hero_user_link_apple = REPLACE(hero_user_link_apple, 'https://taxi1.sangvish.com/', 'https://viberidegh.online/'),
  hero_driver_link_android = REPLACE(hero_driver_link_android, 'https://taxi1.sangvish.com/', 'https://viberidegh.online/'),
  hero_driver_link_apple = REPLACE(hero_driver_link_apple, 'https://taxi1.sangvish.com/', 'https://viberidegh.online/');

UPDATE landing_quicklinks SET
  privacy = REPLACE(REPLACE(privacy, 'Sangvish', 'viberidegh'), 'sangvish', 'viberidegh'),
  terms = REPLACE(REPLACE(terms, 'Sangvish', 'viberidegh'), 'sangvish', 'viberidegh'),
  compliance = REPLACE(REPLACE(compliance, 'Sangvish', 'viberidegh'), 'sangvish', 'viberidegh'),
  dmv = REPLACE(REPLACE(dmv, 'Sangvish', 'viberidegh'), 'sangvish', 'viberidegh')
WHERE privacy LIKE '%sangvish%' OR terms LIKE '%sangvish%' OR compliance LIKE '%sangvish%' OR dmv LIKE '%sangvish%' OR privacy LIKE '%Sangvish%';

UPDATE single_landing_headers SET
  copy_rights = REPLACE(REPLACE(REPLACE(REPLACE(copy_rights, 'sangvish', 'viberidegh'), 'Viberide', 'viberidegh'), ' - Alternativo', ''), '2021 @', '2026 @')
WHERE copy_rights LIKE '%sangvish%' OR copy_rights LIKE '%Viberide%' OR copy_rights LIKE '%Alternativo%';

UPDATE mail_templates SET
  translation_dataset = REPLACE(REPLACE(translation_dataset, 'Sangvish', 'viberidegh'), 'sangvish', 'viberidegh')
WHERE translation_dataset LIKE '%sangvish%' OR translation_dataset LIKE '%Sangvish%';

UPDATE notification_channels SET
  mail_body = REPLACE(REPLACE(mail_body, 'Sangvish', 'viberidegh'), 'sangvish', 'viberidegh'),
  footer_copyrights = REPLACE(REPLACE(footer_copyrights, 'Sangvish', 'viberidegh'), 'sangvish', 'viberidegh')
WHERE mail_body LIKE '%Sangvish%' OR mail_body LIKE '%sangvish%' OR footer_copyrights LIKE '%Sangvish%' OR footer_copyrights LIKE '%sangvish%';
