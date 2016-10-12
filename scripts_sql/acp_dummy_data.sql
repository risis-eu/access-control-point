--
-- Base de données :  `acp_dummy`
--

--
-- Contenu de la table `dictionnary`
--

INSERT INTO `dictionnary` (`entityType`, `name`, `description`, `hasEntityType`) VALUES
('Papier', 'appln_id', 'Application identification. Surrogate key: Technical unique identifier without any business meaning, Domain: Number 0 … 999 999 999', NULL),
('Papier', 'appln_filing_year', 'Year of the application filing date. Derived from attribute APPLN_FILING_DATE (TLS201_APPLN).', NULL),
('Papier', 'appln_first_priority_year', 'Year of the first priority year for the application. Derived from attribute APPLN_PRIORITY_YEAR (TLS204_appln_prior).', NULL),
('Papier', 'appln_kind', 'Kind of Application. Specification of the kind of application.', NULL),
('Papier', 'appln_title', 'Title of the application. ', NULL),
('Institution', 'appln_id', 'Application identification. Surrogate key: Technical unique identifier without any business meaning, Domain: Number 0 … 999 999 999', 'Papier'),
('Institution', 'person_id', 'Person identification. Surrogate key based on the elements in the alternate primary key of table TLS206_PERSON. Domain: Number 1 … 999 999 999', NULL),
('Institution', 'org_name_std', 'Standardized name of the applicant (for moral persons only). Derived from attribute doc_std_name DOC_STD_NAME (TLS208_DOC_STD_NMS).', NULL),
('Institution', 'org_type', 'Type of institutions for the standardized name of the applicant (Univ, Gvt, Firm, Hosp).', NULL),
('Institution', 'ctry_harm', 'Pays harmonised code 2 digit following the ISO 3166 alpha 2.', 'Pays'),
('Pays', 'ctry_harm', 'Pays harmonised code 2 digit following the ISO 3166 alpha 2.', NULL),
('Pays', 'lib_ctry_harm', 'Full name of the country following the ISO 3166 alpha 2.', NULL),
('Pays', 'continent', 'Continent of the country of the applicant.', NULL),
('Pays', 'region', 'Region of the country of the applicant.', NULL);

-- --------------------------------------------------------

--
-- Contenu de la table `entities`
--

INSERT INTO `entities` (`entity`, `description`, `active`) VALUES
('Pays', 'Information about the countries of applicants (ISO country code, continent…)', 1),
('Papier', 'Information about the Nano patents extracted from EPO Patstat version September 2014, and as described in Kahane, B., Mogoutov, A., Cointet, J. P., Villard, L. & Laredo, P., 2015. A dynamic query to delineate emergent science and technology: the case of nano science and technology. Content and technical structure of the Nano S&T Dynamics Infrastructure, RISIS, 47-70.',1),
('Institution', 'Information regarding institutions (standardized name, type…).',1);

-- --------------------------------------------------------

--
-- Structure de la table `Institution`
--

CREATE TABLE IF NOT EXISTS `Institution` (
  `id` varchar(23) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `appln_id` int(10) NOT NULL DEFAULT '0',
  `person_id` int(10) NOT NULL DEFAULT '0',
  `org_name_std` varchar(150) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `org_type` varchar(30) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `ctry_harm` varchar(2) CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Contenu de la table `Institution`
--

INSERT INTO `Institution` (`id`, `appln_id`, `person_id`, `org_name_std`, `org_type`, `ctry_harm`) VALUES
('4-26', 4, 26, 'institution A', 'firm', 'US'),
('5-26', 5, 26, 'institution A', 'firm', 'US'),
('8-41', 8, 41, 'organisme B', 'firm', 'JP');

-- --------------------------------------------------------

--
-- Structure de la table `Papier`
--

CREATE TABLE IF NOT EXISTS `Papier` (
  `id` int(10) NOT NULL DEFAULT '0',
  `appln_id` int(10) NOT NULL DEFAULT '0',
  `appln_filing_year` int(4) DEFAULT NULL,
  `appln_first_priority_year` int(4) DEFAULT NULL,
  `appln_kind` varchar(2) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `appln_title` varchar(3500) CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Contenu de la table `Papier`
--

INSERT INTO `Papier` (`id`, `appln_id`, `appln_filing_year`, `appln_first_priority_year`, `appln_kind`, `appln_title`) VALUES
(4, 4, 2000, 1999, 'A', 'Invention 323'),
(5, 5, 2000, 1999, 'A', 'Brevet 643'),
(8, 8, 2000, 2000, 'A', 'Brevet 235');

-- --------------------------------------------------------

--
-- Structure de la table `Pays`
--

CREATE TABLE IF NOT EXISTS `Pays` (
  `id` varchar(2) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `ctry_harm` varchar(2) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `lib_ctry_harm` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `continent` varchar(40) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `region` varchar(40) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Contenu de la table `Pays`
--

INSERT INTO `Pays` (`id`, `ctry_harm`, `lib_ctry_harm`, `continent`, `region`) VALUES
('JP', 'JP', 'JAPAN', 'Asia', 'Eastern Asia'),
('US', 'US', 'UNITED STATES', 'Northern America', 'Northern America');

