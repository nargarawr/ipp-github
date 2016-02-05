/*
SQL To create tables used for Niceway.to
Written by Craig Knott, 15/10/2015
*/

drop table tb_point;
drop table tb_route;
drop table tb_user;

CREATE TABLE tb_user (
    pk_user_id int not null auto_increment,
    username varchar(32) not null,
    fname varchar(32),
    lname varchar(32),
    email varchar(128),
    location varchar(64),
    bio varchar(1024),
    password varchar(32) not null,
    login_count int not null default 0,
    last_login datetime not null,
    is_admin int not null default 0,
    is_banned int not null default 0,
    is_shadow_banned int not null default 0,
    datetime_created datetime not null,
    datetime_updated datetime not null,
    primary key (pk_user_id)
);

INSERT INTO tb_user (
    username, fname, lname, email, location, bio, password, login_count, last_login, is_admin, is_banned, is_shadow_banned, datetime_created, datetime_updated
) values (
    'cxk', 'Craig', 'Knott', 'cxk01u@gmail.com', 'Nottingham', 'I am a student at the University of Nottingham that loves drives by lakes', MD5('a'), 0, NOW(), 1, 0, 0, NOW(), NOW()
),(
    'abxow', 'Olivia', 'Webster', 'abxow1@nottingham.ac.uk', 'Nottingham', 'I am a PhD student at the University of Nottingham that loves historic sites', MD5('a'), 0, NOW(), 0, 0, 0, NOW(), NOW()
), (
    'demo1', 'Ralph', 'Smith', 'cxk01u@gmail.com', 'St.Ives, Cambridgeshire', 'I like trains', MD5('a'), 0, NOW(), 0, 0,
    0, NOW(), NOW()
), (
    'demo2', 'Lauren', 'Smith', 'cxk01u@gmail.com', 'Oxford, UK', 'I like educational establishments', MD5('a'), 0, 
    NOW(), 0, 0, 0, NOW(), NOW()
), (
    'demo3', 'Matt', 'Ducksworth', 'cxk01u@gmail.com', 'Edinburgh', 'I love the highlands!', MD5('a'), 0,
    NOW(), 0, 0, 0, NOW(), NOW()
), (
    'demo4', 'SHADOW_BANNED', 'USER', 'cxk01u@gmail.com', '', 'i am shadow banned', MD5('a'), 0, NOW(), 0, 0, 1, NOW(), 
    NOW()
);

CREATE TABLE tb_route (
  pk_route_id int not null auto_increment,
  created_by int not null,
  name varchar(64) not null,
  description varchar(1024),
  is_private int not null,
  cost float,
  distance float,
  datetime_created datetime not null,
  datetime_updated datetime not null,
  primary key (pk_route_id),
  foreign key (created_by) references tb_user(pk_user_id)
);
CREATE TABLE tb_point (
    pk_point_id int not null auto_increment,
    fk_route_id int not null,
    name varchar(64) not null,
    description varchar(255),
    latitude varchar(16),
    longitude varchar(16),
    primary key (pk_point_id),
    foreign key (fk_route_id) references tb_route(pk_route_id)
);

CREATE TABLE tb_rating (
  pk_rating_id int not null auto_increment,
  fk_route_id int not null,
  created_by int not null,
  value int,
  primary key (pk_rating_id),
  foreign key (created_by) references tb_user(pk_user_id),
  foreign key (fk_route_id) references tb_route(pk_route_id)
);
CREATE TABLE tb_comment (
  pk_comment_id int not null auto_increment,
  fk_route_id int not null,
  created_by int not null,
  comment varchar(1024),
  primary key (pk_comment_id),
  foreign key (created_by) references tb_user(pk_user_id),
  foreign key (fk_route_id) references tb_route(pk_route_id)
);

CREATE TABLE tb_admin_log (
  pk_admin_log_id int not null auto_increment,
  fk_user_id int not null,
  datetime datetime,
  action varchar(32),
  primary key(pk_admin_log_id),
  foreign key(fk_user_id) references tb_user(pk_user_id)
);

CREATE TABLE tb_site_admin (
  pk_site_admin_id int not null auto_increment,
  is_locked int not null default 0,
  primary key(pk_site_admin_id)
);






CREATE TABLE tb_announcement (
  pk_announcement_id int not null auto_increment,
  message varchar(255) not null,
  created_by int not null,
  datetime_created datetime not null
  primary key (pk_announcement_id),
  foreign key (created_by) references tb_user(pk_user_id)
);