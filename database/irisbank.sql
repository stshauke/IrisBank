-- MySQL dump 10.13  Distrib 8.0.43, for Win64 (x86_64)
--
-- Host: localhost    Database: irisbank
-- ------------------------------------------------------
-- Server version	9.1.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `comptes_bancaires`
--

DROP TABLE IF EXISTS `comptes_bancaires`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comptes_bancaires` (
  `id` int NOT NULL AUTO_INCREMENT,
  `iban` varchar(34) NOT NULL,
  `type` varchar(20) NOT NULL,
  `solde` decimal(15,2) NOT NULL,
  `statut` varchar(20) NOT NULL,
  `created_at` datetime NOT NULL,
  `user_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_B0B5D178FAD56E62` (`iban`),
  KEY `IDX_B0B5D178A76ED395` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comptes_bancaires`
--

LOCK TABLES `comptes_bancaires` WRITE;
/*!40000 ALTER TABLE `comptes_bancaires` DISABLE KEYS */;
INSERT INTO `comptes_bancaires` VALUES (1,'FR76-YBNK-FEE5-699F-3C98-FAE6-8AC','courant',1500.00,'actif','2026-03-11 14:39:53',2),(2,'FR76-YBNK-D0D0-A076-D3DA-9CD0-4A1','courant',1500.00,'actif','2026-03-11 14:39:53',3),(3,'FR76-YBNK-4CC4-195E-1AD2-BE91-592','courant',1550.00,'actif','2026-03-11 14:39:53',4),(5,'FR76-YBNK-51EB-767E-4B68-6026-E37','courant',150.00,'actif','2026-03-11 15:59:20',5),(6,'FR76-YBNK-D82E-55B9-D7E3-940A-442','livret_a',200.00,'actif','2026-03-11 15:59:53',5),(7,'FR76-YBNK-0A73-79FC-4F01-5F9C-9F7','pel',0.00,'actif','2026-03-11 16:00:16',5);
/*!40000 ALTER TABLE `comptes_bancaires` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doctrine_migration_versions`
--

DROP TABLE IF EXISTS `doctrine_migration_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int DEFAULT NULL,
  PRIMARY KEY (`version`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doctrine_migration_versions`
--

LOCK TABLES `doctrine_migration_versions` WRITE;
/*!40000 ALTER TABLE `doctrine_migration_versions` DISABLE KEYS */;
INSERT INTO `doctrine_migration_versions` VALUES ('DoctrineMigrations\\Version20260311142617','2026-03-11 14:26:34',125);
/*!40000 ALTER TABLE `doctrine_migration_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messenger_messages`
--

DROP TABLE IF EXISTS `messenger_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `messenger_messages` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `body` longtext NOT NULL,
  `headers` longtext NOT NULL,
  `queue_name` varchar(190) NOT NULL,
  `created_at` datetime NOT NULL,
  `available_at` datetime NOT NULL,
  `delivered_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750` (`queue_name`,`available_at`,`delivered_at`,`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messenger_messages`
--

LOCK TABLES `messenger_messages` WRITE;
/*!40000 ALTER TABLE `messenger_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `messenger_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` varchar(30) NOT NULL,
  `montant` decimal(15,2) NOT NULL,
  `libelle` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `compte_source_id` int DEFAULT NULL,
  `compte_destinataire_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_EAA81A4C56B22253` (`compte_source_id`),
  KEY `IDX_EAA81A4C6D34063E` (`compte_destinataire_id`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transactions`
--

LOCK TABLES `transactions` WRITE;
/*!40000 ALTER TABLE `transactions` DISABLE KEYS */;
INSERT INTO `transactions` VALUES (1,'depot',1500.00,'Dépôt initial','2026-03-11 14:39:53',NULL,1),(2,'depot',1500.00,'Dépôt initial','2026-03-11 14:39:53',NULL,2),(3,'depot',1500.00,'Dépôt initial','2026-03-11 14:39:53',NULL,3),(4,'depot',500.00,'Dépôt','2026-03-11 16:03:19',NULL,5),(5,'retrait',100.00,'Retrait','2026-03-11 16:05:31',5,NULL),(6,'virement_emis',200.00,'Épargne du mois','2026-03-11 16:07:24',5,6),(7,'virement_recu',200.00,'Virement reçu de FR76-YBNK-51EB-767E-4B68-6026-E37','2026-03-11 16:07:24',5,6),(8,'virement_emis',50.00,'Virement de Sophie Martin','2026-03-11 16:12:39',5,3),(9,'virement_recu',50.00,'Virement reçu de FR76-YBNK-51EB-767E-4B68-6026-E37','2026-03-11 16:12:39',5,3);
/*!40000 ALTER TABLE `transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(180) NOT NULL,
  `roles` json NOT NULL,
  `password` varchar(255) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `date_naissance` date DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_1483A5E9E7927C74` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin','IrisBank','admin@irisbank.fr','[\"ROLE_ADMIN\"]','$2y$13$65G2CNU0nBScZrRewhDYx.EdTC8jvJnkqp6gkTUCid2t8ZciQAk.q',NULL,NULL,NULL,'2026-03-11 14:39:52'),(2,'Dupont','Jean','jean.dupont@email.fr','[\"ROLE_USER\"]','$2y$13$.SMUYYQy5MSXkjfxpBrry.HJZNf2slY8P5x0FVwqwwkhMnRxsdeya','0612345678',NULL,NULL,'2026-03-11 14:39:52'),(3,'Martin','Sophie','sophie.martin@email.fr','[\"ROLE_USER\"]','$2y$13$CLZkXvZ5a8/ol2Fdv5JkwuLS8ZgtQ67OEQ4NSaT5fe2H5lsXaGgGO','0698765432',NULL,NULL,'2026-03-11 14:39:53'),(4,'Bernard','Pierre','pierre.bernard@email.fr','[\"ROLE_USER\"]','$2y$13$XOUXnLUIWzhwZQZ3ouB7x.CFRiUDh0uGBSpkiTeK6P2Pu3FTkX.UK','0611223344',NULL,NULL,'2026-03-11 14:39:53'),(5,'Duran','Alice','alice@test.fr','[\"ROLE_USER\"]','$2y$13$ceN50MK2MJWAcYonM09i/.JSVUt.oko/xQaflXi81LD0oQpXp0S2W','0612345678',NULL,NULL,'2026-03-11 15:44:24');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-11 17:48:58
