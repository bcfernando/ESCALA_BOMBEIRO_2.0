-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 07/12/2025 às 19:20
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `escala_db`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `bombeiros`
--

CREATE TABLE `bombeiros` (
  `id` int(11) NOT NULL,
  `nome_completo` varchar(255) NOT NULL,
  `email` varchar(191) DEFAULT NULL,
  `cpf` varchar(14) DEFAULT NULL COMMENT 'Formato 000.000.000-00',
  `endereco` varchar(255) DEFAULT NULL COMMENT 'Endereço residencial completo',
  `telefone` varchar(20) DEFAULT NULL COMMENT 'Número de telefone principal',
  `telefone_emergencia` varchar(20) DEFAULT NULL COMMENT 'Contato de emergência (nome e telefone)',
  `tipo` enum('BC','Fixo') NOT NULL,
  `pular_ordem` tinyint(1) NOT NULL DEFAULT 0,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `fixo_ref_data` date DEFAULT NULL,
  `fixo_ref_dia_ciclo` tinyint(1) DEFAULT NULL CHECK (`fixo_ref_dia_ciclo` between 1 and 4),
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  `endereco_rua` varchar(255) DEFAULT NULL,
  `endereco_numero` varchar(20) DEFAULT NULL,
  `endereco_bairro` varchar(100) DEFAULT NULL,
  `endereco_cidade` varchar(100) DEFAULT NULL,
  `endereco_uf` varchar(2) DEFAULT NULL,
  `endereco_cep` varchar(10) DEFAULT NULL,
  `telefone_principal` varchar(20) DEFAULT NULL,
  `contato_emergencia_nome` varchar(255) DEFAULT NULL,
  `contato_emergencia_fone` varchar(20) DEFAULT NULL,
  `dados_bancarios` text DEFAULT NULL COMMENT 'Ex: Banco XPTO, Ag: 0001, C/C: 12345-6',
  `tamanho_gandola` varchar(20) DEFAULT NULL,
  `tamanho_camiseta` varchar(20) DEFAULT NULL,
  `tamanho_calca` varchar(20) DEFAULT NULL,
  `tamanho_calcado` varchar(10) DEFAULT NULL,
  `banco_nome` varchar(100) DEFAULT NULL COMMENT 'Nome do Banco',
  `banco_agencia` varchar(20) DEFAULT NULL COMMENT 'Número da Agência (com dígito, se houver)',
  `banco_conta` varchar(30) DEFAULT NULL COMMENT 'Número da Conta Corrente/Poupança (com dígito)',
  `banco_pix` varchar(100) DEFAULT NULL COMMENT 'Chave PIX principal (CPF, e-mail, telefone, aleatória)',
  `usuario_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `bombeiros`
--

INSERT INTO `bombeiros` (`id`, `nome_completo`, `email`, `cpf`, `endereco`, `telefone`, `telefone_emergencia`, `tipo`, `pular_ordem`, `ativo`, `fixo_ref_data`, `fixo_ref_dia_ciclo`, `data_cadastro`, `endereco_rua`, `endereco_numero`, `endereco_bairro`, `endereco_cidade`, `endereco_uf`, `endereco_cep`, `telefone_principal`, `contato_emergencia_nome`, `contato_emergencia_fone`, `dados_bancarios`, `tamanho_gandola`, `tamanho_camiseta`, `tamanho_calca`, `tamanho_calcado`, `banco_nome`, `banco_agencia`, `banco_conta`, `banco_pix`, `usuario_id`) VALUES
(1, 'ANDREA ZART', NULL, NULL, NULL, NULL, NULL, 'BC', 0, 1, NULL, NULL, '2025-08-09 01:06:05', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'ANELI MIOTTO TERNUS', NULL, NULL, NULL, NULL, NULL, 'BC', 0, 1, NULL, NULL, '2025-08-09 01:06:05', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'ANGELICA BOETTCHER', NULL, NULL, NULL, NULL, NULL, 'BC', 0, 1, NULL, NULL, '2025-08-09 01:06:05', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 'BRIAN DEIV HENRICH COSMAN', 'bryan139apenas@gmail.com', NULL, NULL, NULL, NULL, 'Fixo', 0, 1, '2025-12-01', 1, '2025-08-09 01:06:05', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 'CLEIMAR BOETTCHER', NULL, NULL, NULL, NULL, NULL, 'Fixo', 0, 1, '2025-12-01', 3, '2025-08-09 01:06:05', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 'CLEIDIVAN IVAN BENEDIX', NULL, NULL, NULL, NULL, NULL, 'BC', 0, 0, NULL, NULL, '2025-08-09 01:06:05', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, 'CRISTIAN KONCZIKOSKI', NULL, NULL, NULL, NULL, NULL, 'Fixo', 0, 1, '2025-12-01', 4, '2025-08-09 01:06:05', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(8, 'CRISTIANE BOETTCHER', NULL, NULL, NULL, NULL, NULL, 'BC', 0, 1, NULL, NULL, '2025-08-09 01:06:05', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(12, 'KELVIN KERKHOFF', '', NULL, NULL, NULL, NULL, 'Fixo', 0, 0, '2025-08-01', NULL, '2025-08-09 01:06:05', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(13, 'LUIZ FERNANDO HOHN', 'luiizhohn@gmail.com\r\n', NULL, NULL, NULL, NULL, 'BC', 0, 1, NULL, NULL, '2025-08-20 01:52:24', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(14, 'DOUGLAS LUBENOW', NULL, NULL, NULL, NULL, NULL, 'BC', 0, 1, NULL, NULL, '2025-08-20 02:03:35', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(15, 'ELDI GELSI NICHTERWITZ PORTELA', NULL, NULL, NULL, NULL, NULL, 'BC', 0, 1, NULL, NULL, '2025-08-20 02:03:35', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(16, 'JOSÉ NELSO BOTT', NULL, NULL, NULL, NULL, NULL, 'BC', 0, 1, NULL, NULL, '2025-08-20 02:03:35', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(17, 'MAICON MOHR', NULL, NULL, NULL, NULL, NULL, 'Fixo', 0, 1, '2025-12-01', 2, '2025-08-20 02:03:35', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(18, 'MARCLEI NICHTERVITZ', NULL, NULL, NULL, NULL, NULL, 'BC', 0, 1, NULL, NULL, '2025-08-20 02:03:35', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(19, 'PATRICIA MARIA BOSING HOFFMANN', NULL, NULL, NULL, NULL, NULL, 'BC', 0, 1, NULL, NULL, '2025-08-20 02:03:35', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(20, 'PATRICIA BERTOLDI', NULL, NULL, NULL, NULL, NULL, 'BC', 0, 1, NULL, NULL, '2025-08-20 02:03:35', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `chat_mensagens`
--

CREATE TABLE `chat_mensagens` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `destinatario_id` int(11) NOT NULL,
  `mensagem` text NOT NULL,
  `data_hora` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `configuracoes`
--

CREATE TABLE `configuracoes` (
  `chave` varchar(50) NOT NULL,
  `valor` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `configuracoes`
--

INSERT INTO `configuracoes` (`chave`, `valor`) VALUES
('bc_da_vez_id', '14'),
('bc_inicio_ordem_id', '1'),
('bc_ultimo_escolheu_id', NULL),
('ultimo_bc_iniciou_mes', '2');

-- --------------------------------------------------------

--
-- Estrutura para tabela `excecoes_ciclo_fixo`
--

CREATE TABLE `excecoes_ciclo_fixo` (
  `id` int(11) NOT NULL,
  `bombeiro_id` int(11) NOT NULL,
  `data` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `excecoes_ciclo_fixo`
--

INSERT INTO `excecoes_ciclo_fixo` (`id`, `bombeiro_id`, `data`) VALUES
(1, 4, '2025-08-01'),
(6, 4, '2025-10-08'),
(4, 5, '2025-10-02'),
(11, 5, '2025-12-03'),
(5, 7, '2025-10-07'),
(2, 12, '2025-08-02'),
(8, 12, '2025-12-08');

-- --------------------------------------------------------

--
-- Estrutura para tabela `ordem_escolha`
--

CREATE TABLE `ordem_escolha` (
  `id` int(11) NOT NULL,
  `bombeiro_id` int(11) NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `ordem` int(11) NOT NULL,
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `ordem_escolha`
--

INSERT INTO `ordem_escolha` (`id`, `bombeiro_id`, `ativo`, `ordem`, `atualizado_em`) VALUES
(1, 1, 1, 1, '2025-12-07 17:45:58'),
(2, 2, 1, 2, '2025-12-07 17:45:58'),
(3, 3, 1, 3, '2025-12-07 17:45:58'),
(4, 6, 1, 4, '2025-12-07 17:45:58'),
(5, 8, 1, 5, '2025-12-07 17:45:58'),
(6, 14, 1, 6, '2025-12-07 17:45:58'),
(7, 15, 1, 7, '2025-12-07 17:45:58'),
(8, 16, 1, 8, '2025-12-07 17:45:58'),
(9, 13, 1, 9, '2025-12-07 17:45:58'),
(10, 17, 1, 10, '2025-12-07 17:45:58'),
(11, 18, 1, 11, '2025-12-07 17:45:58'),
(12, 20, 1, 12, '2025-12-07 17:45:58'),
(13, 19, 1, 13, '2025-12-07 17:45:58');

-- --------------------------------------------------------

--
-- Estrutura para tabela `plantoes`
--

CREATE TABLE `plantoes` (
  `id` int(11) NOT NULL,
  `bombeiro_id` int(11) NOT NULL,
  `data` date NOT NULL,
  `turno` enum('D','N','I') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `plantoes`
--

INSERT INTO `plantoes` (`id`, `bombeiro_id`, `data`, `turno`) VALUES
(137, 1, '2025-12-03', 'I'),
(135, 2, '2025-12-02', 'I'),
(136, 7, '2025-12-03', '');

-- --------------------------------------------------------

--
-- Estrutura para tabela `trocas`
--

CREATE TABLE `trocas` (
  `id` int(11) NOT NULL,
  `data` date NOT NULL,
  `origem_usuario` int(11) NOT NULL,
  `destino_usuario` int(11) NOT NULL,
  `motivo` varchar(200) DEFAULT NULL,
  `status` enum('pendente','aceita','negada') DEFAULT 'pendente',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `bombeiro_id` int(11) DEFAULT NULL,
  `usuario` varchar(50) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `tipo` enum('admin','bc') NOT NULL,
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `bombeiro_id`, `usuario`, `senha_hash`, `tipo`, `ativo`) VALUES
(1, 'Administrador', NULL, 'admin', '$2y$10$Lvq0pDDvLBe3C86zZT0ma.KWzqEB0i19dMbNJrK1xwGRI8oFQpssa', 'admin', 1),
(6, 'Luiz Fernando Hohn', 13, 'fernando', '$2y$10$jlyQqWTKW5F/CXOAXPxZyu0zJwSgpay7jeZUa3do/ineaF4IOlBzu', 'bc', 1);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `bombeiros`
--
ALTER TABLE `bombeiros`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bombeiros_email` (`email`),
  ADD KEY `idx_bombeiros_usuario` (`usuario_id`);

--
-- Índices de tabela `chat_mensagens`
--
ALTER TABLE `chat_mensagens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `destinatario_id` (`destinatario_id`);

--
-- Índices de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  ADD PRIMARY KEY (`chave`);

--
-- Índices de tabela `excecoes_ciclo_fixo`
--
ALTER TABLE `excecoes_ciclo_fixo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_excecao` (`bombeiro_id`,`data`);

--
-- Índices de tabela `ordem_escolha`
--
ALTER TABLE `ordem_escolha`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ordem_bombeiro` (`bombeiro_id`);

--
-- Índices de tabela `plantoes`
--
ALTER TABLE `plantoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_bombeiro_data_turno` (`bombeiro_id`,`data`,`turno`),
  ADD KEY `idx_data` (`data`);

--
-- Índices de tabela `trocas`
--
ALTER TABLE `trocas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_trocas_data` (`data`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `bombeiros`
--
ALTER TABLE `bombeiros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de tabela `chat_mensagens`
--
ALTER TABLE `chat_mensagens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `excecoes_ciclo_fixo`
--
ALTER TABLE `excecoes_ciclo_fixo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `ordem_escolha`
--
ALTER TABLE `ordem_escolha`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de tabela `plantoes`
--
ALTER TABLE `plantoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=138;

--
-- AUTO_INCREMENT de tabela `trocas`
--
ALTER TABLE `trocas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `chat_mensagens`
--
ALTER TABLE `chat_mensagens`
  ADD CONSTRAINT `chat_mensagens_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `chat_mensagens_ibfk_2` FOREIGN KEY (`destinatario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `excecoes_ciclo_fixo`
--
ALTER TABLE `excecoes_ciclo_fixo`
  ADD CONSTRAINT `excecoes_ciclo_fixo_ibfk_1` FOREIGN KEY (`bombeiro_id`) REFERENCES `bombeiros` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `ordem_escolha`
--
ALTER TABLE `ordem_escolha`
  ADD CONSTRAINT `fk_ordem_bombeiro` FOREIGN KEY (`bombeiro_id`) REFERENCES `bombeiros` (`id`);

--
-- Restrições para tabelas `plantoes`
--
ALTER TABLE `plantoes`
  ADD CONSTRAINT `plantoes_ibfk_1` FOREIGN KEY (`bombeiro_id`) REFERENCES `bombeiros` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
