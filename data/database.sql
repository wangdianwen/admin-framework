-- MySQL dump 10.13  Distrib 5.7.18, for Linux (x86_64)
--
-- Host: localhost    Database: privilege
-- ------------------------------------------------------
-- Server version	5.7.18-0ubuntu0.16.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `privilege_app`
--

DROP TABLE IF EXISTS `privilege_app`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `privilege_app` (
  `pk_app_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `app_name` varchar(32) NOT NULL,
  `cn_name` varchar(32) NOT NULL COMMENT 'app中文名',
  `token` varchar(64) NOT NULL,
  PRIMARY KEY (`pk_app_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='app应用表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `privilege_app`
--

LOCK TABLES `privilege_app` WRITE;
/*!40000 ALTER TABLE `privilege_app` DISABLE KEYS */;
INSERT INTO `privilege_app` VALUES (1,'jhmls','金湖马拉松协会','0378865c2625a5bb75aa78ca6b01e5f5');
/*!40000 ALTER TABLE `privilege_app` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `privilege_menu`
--

DROP TABLE IF EXISTS `privilege_menu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `privilege_menu` (
  `pk_menu_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '菜单id',
  `app` varchar(32) NOT NULL COMMENT '所属应用',
  `resource_id` int(10) unsigned NOT NULL COMMENT '资源id',
  `name` varchar(32) NOT NULL COMMENT '菜单名',
  `fid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父ID',
  `sort` int(2) NOT NULL DEFAULT '0' COMMENT '顺序',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否显示，1显示，0隐藏',
  PRIMARY KEY (`pk_menu_id`),
  UNIQUE KEY `name` (`name`,`fid`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8 COMMENT='crm左侧目录表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `privilege_menu`
--

LOCK TABLES `privilege_menu` WRITE;
/*!40000 ALTER TABLE `privilege_menu` DISABLE KEYS */;
INSERT INTO `privilege_menu` VALUES (1,'sys',0,'系统管理',0,0,1),(2,'',0,'资源管理',1,0,1),(3,'',0,'角色管理',1,0,1),(4,'',0,'用户管理',1,0,1),(5,'',0,'菜单管理',1,0,1),(16,'',10,'菜单列表',5,0,1),(19,'',1,'添加角色',3,0,1),(20,'',3,'角色列表',3,0,1),(21,'',2,'添加资源',2,0,1),(22,'',5,'添加菜单',5,0,1),(23,'',9,'资源列表',2,0,1),(24,'',11,'添加用户',4,0,1),(25,'',7,'用户列表',4,0,1);
/*!40000 ALTER TABLE `privilege_menu` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `privilege_resource`
--

DROP TABLE IF EXISTS `privilege_resource`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `privilege_resource` (
  `pk_resource_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '资源id',
  `app` varchar(32) NOT NULL COMMENT '所属应用',
  `module` varchar(63) NOT NULL COMMENT 'app模块名,/module/controller/action',
  `controller` varchar(63) NOT NULL DEFAULT '' COMMENT '控制器名称,/module/controller/action',
  `action` varchar(63) NOT NULL DEFAULT '' COMMENT '动作名称,/module/controller/action',
  `method` enum('get','post','put','patch','delete') NOT NULL DEFAULT 'get' COMMENT 'http协议中method,第一期先不用',
  `ctime` datetime NOT NULL COMMENT '创建时间',
  `status` tinyint(3) NOT NULL DEFAULT '1' COMMENT '权限状态0:未启用,1:启用',
  `title` text NOT NULL COMMENT '资源描述,如果是菜单',
  `f_resource_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父资源id',
  `location_menu_id` int(5) NOT NULL DEFAULT '0' COMMENT '定位菜单ID',
  `verify_login` varchar(2) NOT NULL DEFAULT 'Y' COMMENT '是否需要登录，Y需要，N不需要',
  `verify_privilege` varchar(2) NOT NULL DEFAULT 'Y' COMMENT '是否需要权限，Y需要，N不需要',
  PRIMARY KEY (`pk_resource_id`),
  UNIQUE KEY `appmodule` (`app`,`module`,`controller`,`action`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8 COMMENT='crm注册资源表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `privilege_resource`
--

LOCK TABLES `privilege_resource` WRITE;
/*!40000 ALTER TABLE `privilege_resource` DISABLE KEYS */;
INSERT INTO `privilege_resource` VALUES (1,'sys','Privilege','Role','addRole','get','2017-07-03 14:25:02',1,'添加角色',0,0,'Y','Y'),(2,'sys','Privilege','Resource','addRes','get','2017-07-03 14:25:02',1,'添加资源',0,0,'Y','Y'),(3,'sys','Privilege','Role','roleList','get','2017-07-03 14:25:02',1,'角色列表',0,0,'Y','Y'),(4,'sys','Privilege','Role','showRolePriv','get','2017-07-03 14:25:02',1,'角色权限',0,0,'Y','Y'),(5,'sys','Privilege','Menu','addMenu','get','2017-07-03 14:25:03',1,'添加菜单',0,0,'Y','Y'),(6,'sys','Privilege','Role','assignRolePriv','get','2017-07-03 14:25:03',1,'角色权限分配',0,20,'Y','Y'),(7,'sys','Privilege','User','userList','get','2017-07-03 14:25:03',1,'用户列表',0,0,'Y','Y'),(9,'sys','Privilege','Resource','resList','get','2017-07-03 14:25:03',1,'资源列表',0,0,'Y','Y'),(10,'sys','Privilege','Menu','menuList','get','2017-07-03 14:25:03',1,'菜单列表',0,0,'Y','Y'),(11,'sys','Privilege','User','addUser','get','2017-07-03 14:25:03',1,'添加用户',0,0,'Y','Y'),(28,'sys','Privilege','Index','logout','get','2017-07-03 14:25:03',1,'注销登录',0,0,'Y','N'),(29,'sys','Privilege','Role','assignRoleUser','get','2017-07-03 14:25:03',1,'分配角色用户',0,20,'Y','Y'),(30,'sys','Privilege','Role','roleUser','get','2017-07-03 14:25:03',1,'角色用户列表',0,20,'Y','Y');
/*!40000 ALTER TABLE `privilege_resource` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `privilege_role`
--

DROP TABLE IF EXISTS `privilege_role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `privilege_role` (
  `pk_role_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '角色id',
  `app` varchar(32) NOT NULL COMMENT '所属应用',
  `name` varchar(63) NOT NULL DEFAULT '' COMMENT '角色名称',
  `admin_email` varchar(64) NOT NULL COMMENT '管理员邮箱',
  `ctime` datetime NOT NULL COMMENT '创建时间',
  `status` tinyint(3) NOT NULL DEFAULT '1' COMMENT '角色状态0:未启用,1:启用',
  PRIMARY KEY (`pk_role_id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户角色表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `privilege_role`
--

LOCK TABLES `privilege_role` WRITE;
/*!40000 ALTER TABLE `privilege_role` DISABLE KEYS */;
/*!40000 ALTER TABLE `privilege_role` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `privilege_role_re_resource`
--

DROP TABLE IF EXISTS `privilege_role_re_resource`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `privilege_role_re_resource` (
  `pk_privilege_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '权限id',
  `app` varchar(32) NOT NULL COMMENT '所属应用',
  `role_id` int(10) unsigned NOT NULL COMMENT '角色id',
  `resource_id` int(10) unsigned NOT NULL COMMENT '资源id',
  `ctime` datetime NOT NULL COMMENT '创建时间',
  `status` tinyint(3) NOT NULL DEFAULT '1' COMMENT '权限状态0:未启用,1:启用',
  PRIMARY KEY (`pk_privilege_id`),
  UNIQUE KEY `app` (`role_id`,`resource_id`,`app`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='角色权限表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `privilege_role_re_resource`
--

LOCK TABLES `privilege_role_re_resource` WRITE;
/*!40000 ALTER TABLE `privilege_role_re_resource` DISABLE KEYS */;
/*!40000 ALTER TABLE `privilege_role_re_resource` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `privilege_role_re_user`
--

DROP TABLE IF EXISTS `privilege_role_re_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `privilege_role_re_user` (
  `pk_relation_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '关系id',
  `app` varchar(32) NOT NULL COMMENT '所属应用',
  `role_id` int(10) unsigned NOT NULL COMMENT '角色id',
  `user_id` int(10) unsigned NOT NULL COMMENT '后台用户id',
  `user_email` varchar(255) NOT NULL COMMENT '后台用户邮箱',
  `ctime` datetime NOT NULL COMMENT '创建时间',
  `status` tinyint(3) NOT NULL DEFAULT '1' COMMENT '用户状态0:未启用,1:启用',
  PRIMARY KEY (`pk_relation_id`),
  UNIQUE KEY `app` (`role_id`,`user_id`,`app`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户角色关系表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `privilege_role_re_user`
--

LOCK TABLES `privilege_role_re_user` WRITE;
/*!40000 ALTER TABLE `privilege_role_re_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `privilege_role_re_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `privilege_user`
--

DROP TABLE IF EXISTS `privilege_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `privilege_user` (
  `pk_user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `app` varchar(32) NOT NULL COMMENT '所属应用',
  `email` varchar(63) NOT NULL,
  `name` varchar(32) NOT NULL COMMENT '中文名',
  `passwd` char(32) NOT NULL COMMENT '密码',
  `phone` varchar(32) NOT NULL COMMENT '联系电话',
  `ctime` datetime NOT NULL COMMENT '创建时间',
  `status` tinyint(3) NOT NULL DEFAULT '1' COMMENT '用户状态',
  `realname` varchar(32) DEFAULT NULL COMMENT '真实姓名',
  PRIMARY KEY (`pk_user_id`),
  UNIQUE KEY `user` (`app`,`name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='管理员表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `privilege_user`
--

LOCK TABLES `privilege_user` WRITE;
/*!40000 ALTER TABLE `privilege_user` DISABLE KEYS */;
INSERT INTO `privilege_user` VALUES (1,'jhmls','shasw2006@163.com','wangdianwen','095dcccae5c703fc920ba6423236deaf','18612649368','2017-07-03 14:06:46',1,'王淀文');
/*!40000 ALTER TABLE `privilege_user` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2017-07-04 16:36:51
