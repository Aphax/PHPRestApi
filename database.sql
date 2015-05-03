-- phpMyAdmin SQL Dump
-- version 4.1.14
-- http://www.phpmyadmin.net
--
-- Client :  127.0.0.1
-- Généré le :  Dim 03 Mai 2015 à 11:50
-- Version du serveur :  5.6.17
-- Version de PHP :  5.5.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Base de données :  `deezer`
--

-- --------------------------------------------------------

--
-- Structure de la table `song`
--

CREATE TABLE IF NOT EXISTS `song` (
  `song_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(40) NOT NULL,
  `duration` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`song_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

--
-- Contenu de la table `song`
--

INSERT INTO `song` (`song_id`, `title`, `duration`) VALUES
(1, 'Hit the Road Jack', 200),
(2, 'I Got a Woman', 252),
(3, 'Rivers Of Babylon', 118);

-- --------------------------------------------------------

--
-- Structure de la table `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL,
  `email` varchar(50) NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Contenu de la table `user`
--

INSERT INTO `user` (`user_id`, `name`, `email`) VALUES
(1, 'Antoine Dupond', 'antoine@gmail.com'),
(2, 'Pedro', 'pedro@gmail.com');

-- --------------------------------------------------------

--
-- Structure de la table `user_song`
--

CREATE TABLE IF NOT EXISTS `user_song` (
  `user_id` int(10) unsigned NOT NULL,
  `song_id` int(10) unsigned NOT NULL,
  KEY `user_id` (`user_id`),
  KEY `song_id` (`song_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Contenu de la table `user_song`
--

INSERT INTO `user_song` (`user_id`, `song_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(2, 2);

--
-- Contraintes pour les tables exportées
--

--
-- Contraintes pour la table `user_song`
--
ALTER TABLE `user_song`
  ADD CONSTRAINT `user_song_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_song_ibfk_2` FOREIGN KEY (`song_id`) REFERENCES `song` (`song_id`) ON DELETE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
