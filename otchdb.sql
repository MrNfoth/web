

CREATE TABLE `должности` (
  `id_Д` int(11) NOT NULL,
  `Название_Д` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `должности`
--

INSERT INTO `должности` (`id_Д`, `Название_Д`) VALUES
(1, 'Дизайнер'),
(2, 'Бухгалтер'),
(3, 'Аналитик'),
(4, 'Программист'),
(5, 'Менеджер'),
(6, 'Тестировщик'),
(7, 'Администратор'),
(8, 'Директор'),
(9, 'Б/Р');

-- --------------------------------------------------------

--
-- Структура таблицы `доходырасходы`
--

CREATE TABLE `доходырасходы` (
  `id_ДР` int(11) NOT NULL,
  `id_Н` int(11) NOT NULL,
  `СуммаД` decimal(12,2) DEFAULT NULL,
  `СуммаР` decimal(12,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE `компании` (
  `id_К` int(11) NOT NULL,
  `id_ВП` int(11) DEFAULT NULL,
  `id_ДР` int(11) DEFAULT NULL,
  `Название_К` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ОГРН` varchar(13) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ИНН` varchar(12) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Адрес` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Малое_предприятие` tinyint(1) DEFAULT NULL,
  `id_Н` int(11) NOT NULL,
  `Дата_началаП` date DEFAULT NULL,
  `Дата_окончанияП` date DEFAULT NULL,
  `Код_приглашения` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_СН` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE `компании_налоги` (
  `id_К` int(11) NOT NULL,
  `id_Н` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `компании_налоги`
--

INSERT INTO `компании_налоги` (`id_К`, `id_Н`) VALUES
(2, 1),
(3, 1),
(4, 1),
(9, 1),
(1, 2),
(2, 2),
(4, 2),
(6, 2),
(7, 2),
(8, 2),
(9, 2),
(1, 3),
(2, 3),
(4, 3),
(5, 3),
(8, 3),
(9, 3),
(10, 3);



CREATE TABLE `виды_подписок` (
  `id_ВП` int(11) NOT NULL,
  `Название_ВП` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Цена` decimal(10,2) DEFAULT NULL,
  `Срок` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT INTO `виды_подписок` (`id_ВП`, `Название_ВП`, `Цена`, `Срок`) VALUES
(1, 'Месячная', '299.00', 30),
(2, 'Квартальная', '799.00', 90),
(3, 'Годовая', '1499.00', 365);


CREATE TABLE `налоги` (
  `id_Н` int(11) NOT NULL,
  `Процент_Н` decimal(5,2) DEFAULT NULL,
  `Описание_Н` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT INTO `налоги` (`id_Н`, `Процент_Н`, `Описание_Н`) VALUES
(1, '25.00', 'Общая система налогообложения'),
(2, '6.00', 'УСН «Доходы»'),
(3, '7.00', 'УСН «Доходы – Расходы»'),
(4, '6.00', 'Самозанятый, работающий с юрлицами'),
(5, '4.00', 'Самозанятый, работающий с физлицами');


CREATE TABLE `системы_налогообложения` (
  `id_СН` int(11) NOT NULL,
  `название_СН` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT INTO `системы_налогообложения` (`id_СН`, `название_СН`) VALUES
(1, 'Общая система'),
(2, 'УСН: доходы'),
(3, 'УСН: доходы-расходы'),
(4, 'Самозанятый (юр. лица)'),
(5, 'Самозанятый (физ. лица)');


CREATE TABLE `сотрудники` (
  `id_С` int(11) NOT NULL,
  `id_К` int(11) DEFAULT NULL,
  `id_Д` int(11) DEFAULT NULL,
  `Фамилия` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Имя` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Отчество` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ИНН` varchar(12) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Email` varchar(255) CHARACTER SET ascii DEFAULT NULL,
  `Логин` varchar(255) CHARACTER SET ascii DEFAULT NULL,
  `Пароль` varchar(255) CHARACTER SET ascii DEFAULT NULL,
  `СуммаЗП` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-
ALTER TABLE `должности`
  ADD PRIMARY KEY (`id_Д`);


ALTER TABLE `доходырасходы`
  ADD PRIMARY KEY (`id_ДР`,`id_Н`),
  ADD KEY `id_Н` (`id_Н`);


ALTER TABLE `компании`
  ADD PRIMARY KEY (`id_К`),
  ADD KEY `id_ВП` (`id_ВП`),
  ADD KEY `id_ДР` (`id_ДР`),
  ADD KEY `tmp_id_Н` (`id_Н`),
  ADD KEY `id_СН` (`id_СН`);

ALTER TABLE `компании_налоги`
  ADD PRIMARY KEY (`id_К`,`id_Н`),
  ADD KEY `id_Н` (`id_Н`);

ALTER TABLE `виды_подписок`
  ADD PRIMARY KEY (`id_ВП`);

ALTER TABLE `налоги`
  ADD PRIMARY KEY (`id_Н`);


ALTER TABLE `системы_налогообложения`
  ADD PRIMARY KEY (`id_СН`);


ALTER TABLE `сотрудники`
  ADD PRIMARY KEY (`id_С`),
  ADD KEY `id_К` (`id_К`),
  ADD KEY `id_Д` (`id_Д`);


ALTER TABLE `должности`
  MODIFY `id_Д` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

ALTER TABLE `системы_налогообложения`
  MODIFY `id_СН` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

ALTER TABLE `доходырасходы`
  ADD CONSTRAINT `доходырасходы_ibfk_1` FOREIGN KEY (`id_Н`) REFERENCES `налоги` (`id_Н`);

ALTER TABLE `компании`
  ADD CONSTRAINT `fk_компании_налоги` FOREIGN KEY (`id_Н`) REFERENCES `налоги` (`id_Н`) ON UPDATE CASCADE,
  ADD CONSTRAINT `компании_ibfk_1` FOREIGN KEY (`id_ВП`) REFERENCES `виды_подписок` (`id_ВП`),
  ADD CONSTRAINT `компании_ibfk_2` FOREIGN KEY (`id_ДР`) REFERENCES `доходырасходы` (`id_ДР`);

ALTER TABLE `компании_налоги`
  ADD CONSTRAINT `компании_налоги_ibfk_1` FOREIGN KEY (`id_К`) REFERENCES `компании` (`id_К`),
  ADD CONSTRAINT `компании_налоги_ibfk_2` FOREIGN KEY (`id_Н`) REFERENCES `налоги` (`id_Н`);

ALTER TABLE `сотрудники`
  ADD CONSTRAINT `сотрудники_ibfk_1` FOREIGN KEY (`id_К`) REFERENCES `компании` (`id_К`);
COMMIT;
