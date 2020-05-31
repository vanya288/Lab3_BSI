CREATE TABLE `public`.incorrect_logins (id int(11) NOT NULL, time timestamp NOT NULL comment 'login time', session_id text NOT NULL comment 'user session identifier', id_address bigint(20) NOT NULL comment 'IP address of user', computer varchar(255) comment 'conputer identifier - if available in a cookie', PRIMARY KEY (id)) comment='a register of login trials from non registered logins';
CREATE TABLE `public`.ip_address (id bigint(20) NOT NULL, ok_login_num bigint(20) DEFAULT 0 NOT NULL comment 'number of correct logins from the address', bad_login_num int(11) DEFAULT 0 NOT NULL comment 'number of incorrect logins from the address', last_bad_login_num smallint(6) DEFAULT 0 NOT NULL comment 'number of last subsequent incorrect logins from the address', permanent_lock tinyint DEFAULT false NOT NULL comment '1- permanent lock of the IP address; 0 - no permanent lock', temp_lock timestamp NULL comment 'end time of temporary lock', adres_IP varchar(255) NOT NULL comment 'IP address', PRIMARY KEY (id)) comment='IP addresses list';
CREATE TABLE `public`.message (id int(11) NOT NULL, name varchar(255) NOT NULL comment 'name of the message', type varchar(20) comment 'type of the message (private/public)', message varchar(255) NOT NULL comment 'message text', deleted tinyint DEFAULT 0 NOT NULL comment 'existing message - 0, deleted - 1', PRIMARY KEY (id));
CREATE TABLE `public`.privilege (id int(11) NOT NULL, name varchar(100) NOT NULL UNIQUE comment 'name of a privilege', PRIMARY KEY (id)) comment='list of privileges defined in the system';
CREATE TABLE `public`.role (id smallint(6) NOT NULL, name varchar(30) NOT NULL UNIQUE comment 'role name', PRIMARY KEY (id)) comment='user roles';
CREATE TABLE `public`.role_privilege (id int(11) NOT NULL, id_role smallint(6) NOT NULL comment 'role', id_privilege int(11) NOT NULL comment 'privilege', PRIMARY KEY (id)) comment='privilege list assigned to a role in the system';
CREATE TABLE `public`.status (id int(11) NOT NULL, status varchar(255) NOT NULL comment 'account status name', PRIMARY KEY (id));
CREATE TABLE `public`.`user` (id int(11) NOT NULL, login varchar(30) NOT NULL UNIQUE, email varchar(60) NOT NULL UNIQUE, hash varchar(255) NOT NULL comment 'password hash or HMAC value', salt varchar(10) comment 'salt to use in password hashing', sms_code varchar(6) comment 'security code sent via sms or e-mail', code_timelife timestamp NULL comment 'timelife of security code', security_question varchar(255) comment 'additional security question used while password recovering', answer varchar(255) comment 'security question answer', lockout_time timestamp NULL comment 'time to which user account is blocked', session_id blob comment 'user session identifier', id_status int(11) NOT NULL comment 'account status', PRIMARY KEY (id));
CREATE TABLE user_activity (id bigint(20) NOT NULL, id_user int(11) NOT NULL comment 'user that run the action', action_taken varchar(255) NOT NULL comment 'sort of action: create, modify,delete', table_affected varchar(100) comment 'name of affected table', row_number int(11) comment 'affected row number', previous_data varchar(1000) comment 'a record content before action', new_data varchar(1000) comment 'new content of record (fields separated with semicolons)', PRIMARY KEY (id)) comment='log of user activity';
CREATE TABLE `public`.user_login (id int(11) NOT NULL, time timestamp NOT NULL comment 'login time', correct tinyint NOT NULL comment '0 - incorrect login; 1- correct login', id_user int(11) NOT NULL comment 'user id', computer varchar(255) comment 'conputer identifier - if available in a cookie', `session` varchar(255) comment 'user session identifier', id_address bigint(20) NOT NULL comment 'IP address id', PRIMARY KEY (id)) comment='zapis logowań użytkowników do systemu';
CREATE TABLE `public`.user_privilege (id int(11) NOT NULL, id_user int(11) NOT NULL comment 'user identifier', id_privilege int(11) NOT NULL comment 'privilege identifier', PRIMARY KEY (id)) comment='user privileges list';
CREATE TABLE `public`.user_role (id int(11) NOT NULL, id_role smallint(6) comment 'role identifier', id_user int(11) NOT NULL comment 'user identifier', grant_time date NOT NULL comment 'time when privilege was granted to the user', exp_time date comment 'time when privilege was revoken', PRIMARY KEY (id)) comment='list of user roles';
ALTER TABLE `public`.`user` ADD CONSTRAINT FKuser674283 FOREIGN KEY (id_status) REFERENCES `public`.status (id);
ALTER TABLE `public`.incorrect_logins ADD CONSTRAINT FKincorrect_671533 FOREIGN KEY (id_address) REFERENCES `public`.ip_address (id) ON UPDATE Cascade ON DELETE Restrict;
ALTER TABLE `public`.user_role ADD CONSTRAINT FKuser_role747040 FOREIGN KEY (id_role) REFERENCES `public`.role (id) ON UPDATE Cascade ON DELETE Restrict;
ALTER TABLE `public`.role_privilege ADD CONSTRAINT FKrole_privi282874 FOREIGN KEY (id_privilege) REFERENCES `public`.privilege (id) ON UPDATE Cascade ON DELETE Restrict;
ALTER TABLE `public`.user_privilege ADD CONSTRAINT FKuser_privi256705 FOREIGN KEY (id_privilege) REFERENCES `public`.privilege (id) ON UPDATE Cascade ON DELETE Restrict;
ALTER TABLE `public`.user_role ADD CONSTRAINT FKuser_role933066 FOREIGN KEY (id_user) REFERENCES `public`.`user` (id);
ALTER TABLE `public`.role_privilege ADD CONSTRAINT FKrole_privi564965 FOREIGN KEY (id_role) REFERENCES `public`.role (id);
ALTER TABLE `public`.user_privilege ADD CONSTRAINT FKuser_privi725630 FOREIGN KEY (id_privilege) REFERENCES `public`.`user` (id);
ALTER TABLE `public`.user_login ADD CONSTRAINT FKuser_login311804 FOREIGN KEY (id_user) REFERENCES `public`.`user` (id);
ALTER TABLE `public`.user_login ADD CONSTRAINT FKuser_login880675 FOREIGN KEY (id_address) REFERENCES `public`.ip_address (id);
ALTER TABLE user_activity ADD CONSTRAINT FKuser_activ41931 FOREIGN KEY (id_user) REFERENCES `public`.`user` (id);


INSERT INTO `status` (`id`, `status`) VALUES
(1, 'not confirmed'),
(2, 'active'),
(3, 'password change'),
(4, 'temporarily locked'),
(5, 'permanently locked'),
(6, 'deleted');


INSERT INTO public.`user` (`id`, `login`, `email`, `hash`, `salt`, `sms_code`, `code_timelife`, `security_question`, `answer`, `lockout_time`, `session_id`, `id_status`) VALUES
(1, 'john', 'johny@gmail.com', '552d29f9290b9521e6016c2296fa4511', 'sF5%gR', NULL, NULL, NULL, NULL, NULL, NULL, 2),
(2, 'susie', 'susie@gmail.com', '8c90f286786c7f3b96564e1e88e0ddab', 'j67R', NULL, NULL, NULL, NULL, NULL, NULL, 5),
(3, 'anie', 'anie@gmail.com', 'dcb710a566c2a24c8bfaf83618e728f7', 'sdfgh54', NULL, NULL, NULL, NULL, NULL, NULL, 1);


INSERT INTO `public`.message(id, name, type, message, deleted) VALUES 
(1, 'New Intel technology', 'public', 'Intel has announced a new processor for desktops', false),
(2, 'Intel shares raising', 'private', 'brokers announce: Intel shares will go up!', false),
(3, 'New graphic card from NVidia', 'public', 'NVidia has announced a new graphic card for desktops', false),
(4, 'Airplane crash', 'public', 'A passenger plane has crashed in Europe', false);

