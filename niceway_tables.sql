/*
SQL To create tables used for Niceway.to
Written by Craig Knott, 15/10/2015
*/


drop table tb_user;
CREATE TABLE tb_user (
    pk_user_id int not null auto_increment,
    username varchar(32) not null,
    fname varchar(32),
    lname varchar(32),
    email varchar(128),
    location varchar(64),
    password varchar(32) not null,
    login_count int not null default 0,
    last_login datetime,
    is_admin int not null default 0,
    is_banned int not null default 0,
    is_shadow_banned int not null default 0,
    datetime_created datetime not null,
    datetime_updated datetime not null,
    primary key (pk_user_id)
);

insert into tb_user (
  pk_user_id,username,fname,lname,location,login_count,last_login,is_admin,
  is_banned,is_shadow_banned,datetime_created,datetime_updated,password,email
) values (
    1, 'cxk01u', 'Craig', 'Knott', 'Nottingham', 0, NOW(), 1, 0, 0, NOW(), NOW(), MD5('a'), 'cxk01u@googlemail.com'
);

CREATE TABLE tb_announcement (
  pk_announcement_id int not null auto_increment,
  message varchar(255) not null,
  created_by int not null,
  datetime_created datetime not null
  primary key (pk_announcement_id),
  foreign key (created_by) references tb_user(pk_user_id)
);

CREATE TABLE tb_route (
  pk_route_id int not null auto_increment,
  created_by int not null,
  name varchar(64) not null,
  description varchar(1024),
  privacy int not null,
  cost float,
  distance float,
  primary key (pk_route_id),
  foreign key (created_by) references tb_user(pk_user_id)
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

CREATE TABLE tb_point (
  pk_point_id int not null auto_increment,
  fk_route_id int not null,
  name varchar(64) not null,
  short_desc varchar(255),
  latitude float,
  longitude float
  primary key (pk_point_id),
  foreign key (fk_route_id) references tb_route(pk_route_id)
);
