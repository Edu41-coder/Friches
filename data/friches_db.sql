-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 08-11-2025 a las 22:39:29
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `friches_db`
--

DELIMITER $$
--
-- Procedimientos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `chercher_friches_bbox` (IN `p_lat_min` DECIMAL(10,7), IN `p_lon_min` DECIMAL(10,7), IN `p_lat_max` DECIMAL(10,7), IN `p_lon_max` DECIMAL(10,7))   BEGIN
    SELECT *
    FROM friches
    WHERE latitude BETWEEN p_lat_min AND p_lat_max
      AND longitude BETWEEN p_lon_min AND p_lon_max
    ORDER BY comm_nom, site_nom;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `chercher_friches_rayon` (IN `p_latitude` DECIMAL(10,7), IN `p_longitude` DECIMAL(10,7), IN `p_rayon_km` DECIMAL(10,2))   BEGIN
    SELECT 
        *,
        distance_haversine(p_latitude, p_longitude, latitude, longitude) as distance_km
    FROM friches
    WHERE latitude IS NOT NULL 
      AND longitude IS NOT NULL
      AND distance_haversine(p_latitude, p_longitude, latitude, longitude) <= p_rayon_km
    ORDER BY distance_km;
END$$

--
-- Funciones
--
CREATE DEFINER=`root`@`localhost` FUNCTION `distance_haversine` (`lat1` DECIMAL(10,7), `lon1` DECIMAL(10,7), `lat2` DECIMAL(10,7), `lon2` DECIMAL(10,7)) RETURNS DECIMAL(10,2) DETERMINISTIC BEGIN
    DECLARE R DECIMAL(10,2) DEFAULT 6371; -- Rayon de la Terre en km
    DECLARE dLat DECIMAL(10,7);
    DECLARE dLon DECIMAL(10,7);
    DECLARE a DECIMAL(20,10);
    DECLARE c DECIMAL(20,10);
    
    SET dLat = RADIANS(lat2 - lat1);
    SET dLon = RADIANS(lon2 - lon1);
    
    SET a = SIN(dLat/2) * SIN(dLat/2) + 
            COS(RADIANS(lat1)) * COS(RADIANS(lat2)) * 
            SIN(dLon/2) * SIN(dLon/2);
    
    SET c = 2 * ATAN2(SQRT(a), SQRT(1-a));
    
    RETURN R * c;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `friches`
--

CREATE TABLE `friches` (
  `id` int(11) NOT NULL,
  `site_id` varchar(50) NOT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `site_nom` varchar(500) DEFAULT NULL,
  `site_type` varchar(100) DEFAULT NULL,
  `site_identif_date` date DEFAULT NULL,
  `site_actu_date` date DEFAULT NULL,
  `site_url` text DEFAULT NULL,
  `site_securite` varchar(100) DEFAULT NULL,
  `site_occupation` varchar(100) DEFAULT NULL,
  `site_statut` varchar(100) DEFAULT NULL,
  `activite_libelle` text DEFAULT NULL,
  `comm_nom` varchar(200) DEFAULT NULL,
  `comm_insee` varchar(10) DEFAULT NULL,
  `bati_type` varchar(100) DEFAULT NULL,
  `bati_nombre` decimal(10,2) DEFAULT NULL,
  `bati_pollution` varchar(100) DEFAULT NULL,
  `bati_vacance` varchar(100) DEFAULT NULL,
  `bati_patrimoine` varchar(100) DEFAULT NULL,
  `bati_etat` varchar(100) DEFAULT NULL,
  `local_ancien_annee` decimal(10,2) DEFAULT NULL,
  `local_recent_annee` decimal(10,2) DEFAULT NULL,
  `proprio_type` varchar(255) DEFAULT NULL,
  `proprio_personne` varchar(100) DEFAULT NULL,
  `proprio_nom` varchar(500) DEFAULT NULL,
  `sol_pollution_existe` varchar(100) DEFAULT NULL,
  `sol_pollution_origine` varchar(255) DEFAULT NULL,
  `unite_fonciere_surface` decimal(15,2) DEFAULT NULL,
  `unite_fonciere_refcad` text DEFAULT NULL,
  `urba_zone_type` varchar(100) DEFAULT NULL,
  `urba_zone_lib` text DEFAULT NULL,
  `urba_doc_type` varchar(100) DEFAULT NULL,
  `source_nom` varchar(255) DEFAULT NULL,
  `source_url` text DEFAULT NULL,
  `source_producteur` varchar(255) DEFAULT NULL,
  `geompoint` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Table principale contenant les informations sur les friches en France';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `friches_audit`
--

CREATE TABLE `friches_audit` (
  `audit_id` int(11) NOT NULL,
  `site_id` varchar(50) NOT NULL,
  `action_type` enum('INSERT','UPDATE','DELETE') NOT NULL,
  `old_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_data`)),
  `new_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_data`)),
  `user_name` varchar(100) DEFAULT NULL,
  `action_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `friches_domtom`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `friches_domtom` (
`id` int(11)
,`site_id` varchar(50)
,`longitude` decimal(10,7)
,`latitude` decimal(10,7)
,`site_nom` varchar(500)
,`site_type` varchar(100)
,`site_identif_date` date
,`site_actu_date` date
,`site_url` text
,`site_securite` varchar(100)
,`site_occupation` varchar(100)
,`site_statut` varchar(100)
,`activite_libelle` text
,`comm_nom` varchar(200)
,`comm_insee` varchar(10)
,`bati_type` varchar(100)
,`bati_nombre` decimal(10,2)
,`bati_pollution` varchar(100)
,`bati_vacance` varchar(100)
,`bati_patrimoine` varchar(100)
,`bati_etat` varchar(100)
,`local_ancien_annee` decimal(10,2)
,`local_recent_annee` decimal(10,2)
,`proprio_type` varchar(255)
,`proprio_personne` varchar(100)
,`proprio_nom` varchar(500)
,`sol_pollution_existe` varchar(100)
,`sol_pollution_origine` varchar(255)
,`unite_fonciere_surface` decimal(15,2)
,`unite_fonciere_refcad` text
,`urba_zone_type` varchar(100)
,`urba_zone_lib` text
,`urba_doc_type` varchar(100)
,`source_nom` varchar(255)
,`source_url` text
,`source_producteur` varchar(255)
,`geompoint` varchar(255)
,`created_at` timestamp
,`updated_at` timestamp
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `friches_metropole`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `friches_metropole` (
`id` int(11)
,`site_id` varchar(50)
,`longitude` decimal(10,7)
,`latitude` decimal(10,7)
,`site_nom` varchar(500)
,`site_type` varchar(100)
,`site_identif_date` date
,`site_actu_date` date
,`site_url` text
,`site_securite` varchar(100)
,`site_occupation` varchar(100)
,`site_statut` varchar(100)
,`activite_libelle` text
,`comm_nom` varchar(200)
,`comm_insee` varchar(10)
,`bati_type` varchar(100)
,`bati_nombre` decimal(10,2)
,`bati_pollution` varchar(100)
,`bati_vacance` varchar(100)
,`bati_patrimoine` varchar(100)
,`bati_etat` varchar(100)
,`local_ancien_annee` decimal(10,2)
,`local_recent_annee` decimal(10,2)
,`proprio_type` varchar(255)
,`proprio_personne` varchar(100)
,`proprio_nom` varchar(500)
,`sol_pollution_existe` varchar(100)
,`sol_pollution_origine` varchar(255)
,`unite_fonciere_surface` decimal(15,2)
,`unite_fonciere_refcad` text
,`urba_zone_type` varchar(100)
,`urba_zone_lib` text
,`urba_doc_type` varchar(100)
,`source_nom` varchar(255)
,`source_url` text
,`source_producteur` varchar(255)
,`geompoint` varchar(255)
,`created_at` timestamp
,`updated_at` timestamp
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `stats_par_commune`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `stats_par_commune` (
`comm_nom` varchar(200)
,`comm_insee` varchar(10)
,`nb_friches` bigint(21)
,`nb_sans_projet` decimal(22,0)
,`nb_avec_projet` decimal(22,0)
,`nb_reconverties` decimal(22,0)
,`nb_pollues` decimal(22,0)
,`surface_totale` decimal(37,2)
,`longitude_moyenne` decimal(14,11)
,`latitude_moyenne` decimal(14,11)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `stats_par_type`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `stats_par_type` (
`site_type` varchar(100)
,`nb_sites` bigint(21)
,`pourcentage` decimal(26,2)
,`surface_totale` decimal(37,2)
,`surface_moyenne` decimal(19,6)
);

-- --------------------------------------------------------

--
-- Estructura para la vista `friches_domtom`
--
DROP TABLE IF EXISTS `friches_domtom`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `friches_domtom`  AS SELECT `friches`.`id` AS `id`, `friches`.`site_id` AS `site_id`, `friches`.`longitude` AS `longitude`, `friches`.`latitude` AS `latitude`, `friches`.`site_nom` AS `site_nom`, `friches`.`site_type` AS `site_type`, `friches`.`site_identif_date` AS `site_identif_date`, `friches`.`site_actu_date` AS `site_actu_date`, `friches`.`site_url` AS `site_url`, `friches`.`site_securite` AS `site_securite`, `friches`.`site_occupation` AS `site_occupation`, `friches`.`site_statut` AS `site_statut`, `friches`.`activite_libelle` AS `activite_libelle`, `friches`.`comm_nom` AS `comm_nom`, `friches`.`comm_insee` AS `comm_insee`, `friches`.`bati_type` AS `bati_type`, `friches`.`bati_nombre` AS `bati_nombre`, `friches`.`bati_pollution` AS `bati_pollution`, `friches`.`bati_vacance` AS `bati_vacance`, `friches`.`bati_patrimoine` AS `bati_patrimoine`, `friches`.`bati_etat` AS `bati_etat`, `friches`.`local_ancien_annee` AS `local_ancien_annee`, `friches`.`local_recent_annee` AS `local_recent_annee`, `friches`.`proprio_type` AS `proprio_type`, `friches`.`proprio_personne` AS `proprio_personne`, `friches`.`proprio_nom` AS `proprio_nom`, `friches`.`sol_pollution_existe` AS `sol_pollution_existe`, `friches`.`sol_pollution_origine` AS `sol_pollution_origine`, `friches`.`unite_fonciere_surface` AS `unite_fonciere_surface`, `friches`.`unite_fonciere_refcad` AS `unite_fonciere_refcad`, `friches`.`urba_zone_type` AS `urba_zone_type`, `friches`.`urba_zone_lib` AS `urba_zone_lib`, `friches`.`urba_doc_type` AS `urba_doc_type`, `friches`.`source_nom` AS `source_nom`, `friches`.`source_url` AS `source_url`, `friches`.`source_producteur` AS `source_producteur`, `friches`.`geompoint` AS `geompoint`, `friches`.`created_at` AS `created_at`, `friches`.`updated_at` AS `updated_at` FROM `friches` WHERE (`friches`.`longitude` < -5.5 OR `friches`.`longitude` > 10.0 OR `friches`.`latitude` < 41.0 OR `friches`.`latitude` > 51.5) AND `friches`.`longitude` is not null AND `friches`.`latitude` is not null ;

-- --------------------------------------------------------

--
-- Estructura para la vista `friches_metropole`
--
DROP TABLE IF EXISTS `friches_metropole`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `friches_metropole`  AS SELECT `friches`.`id` AS `id`, `friches`.`site_id` AS `site_id`, `friches`.`longitude` AS `longitude`, `friches`.`latitude` AS `latitude`, `friches`.`site_nom` AS `site_nom`, `friches`.`site_type` AS `site_type`, `friches`.`site_identif_date` AS `site_identif_date`, `friches`.`site_actu_date` AS `site_actu_date`, `friches`.`site_url` AS `site_url`, `friches`.`site_securite` AS `site_securite`, `friches`.`site_occupation` AS `site_occupation`, `friches`.`site_statut` AS `site_statut`, `friches`.`activite_libelle` AS `activite_libelle`, `friches`.`comm_nom` AS `comm_nom`, `friches`.`comm_insee` AS `comm_insee`, `friches`.`bati_type` AS `bati_type`, `friches`.`bati_nombre` AS `bati_nombre`, `friches`.`bati_pollution` AS `bati_pollution`, `friches`.`bati_vacance` AS `bati_vacance`, `friches`.`bati_patrimoine` AS `bati_patrimoine`, `friches`.`bati_etat` AS `bati_etat`, `friches`.`local_ancien_annee` AS `local_ancien_annee`, `friches`.`local_recent_annee` AS `local_recent_annee`, `friches`.`proprio_type` AS `proprio_type`, `friches`.`proprio_personne` AS `proprio_personne`, `friches`.`proprio_nom` AS `proprio_nom`, `friches`.`sol_pollution_existe` AS `sol_pollution_existe`, `friches`.`sol_pollution_origine` AS `sol_pollution_origine`, `friches`.`unite_fonciere_surface` AS `unite_fonciere_surface`, `friches`.`unite_fonciere_refcad` AS `unite_fonciere_refcad`, `friches`.`urba_zone_type` AS `urba_zone_type`, `friches`.`urba_zone_lib` AS `urba_zone_lib`, `friches`.`urba_doc_type` AS `urba_doc_type`, `friches`.`source_nom` AS `source_nom`, `friches`.`source_url` AS `source_url`, `friches`.`source_producteur` AS `source_producteur`, `friches`.`geompoint` AS `geompoint`, `friches`.`created_at` AS `created_at`, `friches`.`updated_at` AS `updated_at` FROM `friches` WHERE `friches`.`longitude` >= -5.5 AND `friches`.`longitude` <= 10.0 AND `friches`.`latitude` >= 41.0 AND `friches`.`latitude` <= 51.5 AND `friches`.`longitude` is not null AND `friches`.`latitude` is not null ;

-- --------------------------------------------------------

--
-- Estructura para la vista `stats_par_commune`
--
DROP TABLE IF EXISTS `stats_par_commune`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `stats_par_commune`  AS SELECT `friches`.`comm_nom` AS `comm_nom`, `friches`.`comm_insee` AS `comm_insee`, count(0) AS `nb_friches`, sum(case when `friches`.`site_statut` = 'friche sans projet' then 1 else 0 end) AS `nb_sans_projet`, sum(case when `friches`.`site_statut` = 'friche avec projet' then 1 else 0 end) AS `nb_avec_projet`, sum(case when `friches`.`site_statut` = 'friche reconvertie' then 1 else 0 end) AS `nb_reconverties`, sum(case when `friches`.`sol_pollution_existe` = 'pollution avérée' then 1 else 0 end) AS `nb_pollues`, sum(`friches`.`unite_fonciere_surface`) AS `surface_totale`, avg(`friches`.`longitude`) AS `longitude_moyenne`, avg(`friches`.`latitude`) AS `latitude_moyenne` FROM `friches` WHERE `friches`.`comm_nom` is not null GROUP BY `friches`.`comm_nom`, `friches`.`comm_insee` ORDER BY count(0) DESC ;

-- --------------------------------------------------------

--
-- Estructura para la vista `stats_par_type`
--
DROP TABLE IF EXISTS `stats_par_type`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `stats_par_type`  AS SELECT `friches`.`site_type` AS `site_type`, count(0) AS `nb_sites`, round(count(0) * 100.0 / (select count(0) from `friches`),2) AS `pourcentage`, sum(`friches`.`unite_fonciere_surface`) AS `surface_totale`, avg(`friches`.`unite_fonciere_surface`) AS `surface_moyenne` FROM `friches` WHERE `friches`.`site_type` is not null GROUP BY `friches`.`site_type` ORDER BY count(0) DESC ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `friches`
--
ALTER TABLE `friches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `site_id` (`site_id`),
  ADD KEY `idx_site_id` (`site_id`),
  ADD KEY `idx_coords` (`longitude`,`latitude`),
  ADD KEY `idx_commune` (`comm_nom`,`comm_insee`),
  ADD KEY `idx_statut` (`site_statut`),
  ADD KEY `idx_type` (`site_type`),
  ADD KEY `idx_pollution` (`sol_pollution_existe`);

--
-- Indices de la tabla `friches_audit`
--
ALTER TABLE `friches_audit`
  ADD PRIMARY KEY (`audit_id`),
  ADD KEY `idx_site_id` (`site_id`),
  ADD KEY `idx_action_date` (`action_date`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `friches`
--
ALTER TABLE `friches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `friches_audit`
--
ALTER TABLE `friches_audit`
  MODIFY `audit_id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
