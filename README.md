# Módulo Plantão — Zabbix 7.4 (Versão Multiequipes & Escala Flexível)

Módulo customizado para gerenciar escalas de plantão técnico diretamente na interface nativa do Zabbix 7.4. Esta versão foi totalmente refatorada para suportar o isolamento de escalas por equipes e permitir flexibilidade total de agendamento (por dia ou por semana fechada).

Desenvolvido para integração com plataformas de ligações automatizadas via mídia type (como LIGMEE ou similares), automatizando a atualização do campo destination_number baseado no plantonista ativo.

---

## 🚀 Novidades desta Versão

* Filtro por Equipes: Integração com os Grupos de Usuários nativos do Zabbix (usrgrp), permitindo gerenciar calendários e listas de telefones de forma 100% isolada por time.
* Escala Flexível por Dia: Suporte a alterações cirúrgicas na escala. Agora é possível adicionar, alterar ou remover o plantonista de um dia específico (útil para trocas de última hora).
* Escala em Massa por Semana: Botão dedicado para replicar a dupla de técnicos (Principal + Reserva) de segunda a domingo com apenas um clique.
* Remoção Individual: Botão de exclusão [x] mapeado dentro de cada quadrante diário do calendário.

---

## 📋 Requisitos

* Zabbix: Versão 7.4.x (Frontend PHP)
* Banco de Dados: MySQL 8.0+ ou MariaDB 10.x+
* PHP: Versão 8.2 ou superior (compatível com o Zabbix 7.4)
* Permissões: Usuário com perfil de Admin ou Super Admin no Zabbix para gerenciar as escalas.

---

## 🛠️ Estrutura de Banco de Dados (MySQL)

O módulo utiliza duas tabelas customizadas no banco de dados do Zabbix. Caso esteja implantando do zero, certifique-se de criá-las no MySQL:

Tabela 1: module_plantao_phones
CREATE TABLE IF NOT EXISTS module_plantao_phones (
    userid BIGINT NOT NULL,
    phone VARCHAR(32) NOT NULL DEFAULT '',
    PRIMARY KEY (userid)
);

Tabela 2: module_plantao_schedule
CREATE TABLE IF NOT EXISTS module_plantao_schedule (
    scheduleid BIGINT NOT NULL AUTO_INCREMENT,
    usrgrpid BIGINT NOT NULL,
    userid BIGINT NOT NULL,
    role_type VARCHAR(20) NOT NULL,
    schedule_date DATE NOT NULL,
    created_by BIGINT NOT NULL DEFAULT 0,
    created_at INT NOT NULL DEFAULT 0,
    PRIMARY KEY (scheduleid),
    UNIQUE KEY unique_plantao (usrgrpid, schedule_date, role_type)
);

---

## 📦 Instalação do Módulo

1. Copie a pasta do módulo para o diretório de módulos do frontend do seu Zabbix:
   /usr/share/zabbix/ui/modules/plantao/
2. Acesse a interface web do Zabbix como Super Admin.
3. Vá em Administration -> General -> Modules.
4. Clique em Scan directory.
5. Localize o módulo Plantão na lista e clique em Enable.
6. O menu "Plantão" aparecerá instantaneamente na barra lateral esquerda.

---

## 📝 Como Utilizar

1. Cadastrar Telefones: Acesse o menu Plantão -> Telefones, selecione o seu time no filtro superior e insira os números de telefone correspondentes a cada técnico do grupo.
2. Planejar Escala: Acesse Plantão -> Escala, selecione o seu time. Clique no dia desejado no calendário, escolha o Técnico Principal e o Reserva no painel inferior.
3. Salvar: Escolha entre aplicar a escala apenas para o dia selecionado (Salvar Apenas Este Dia) ou expandi-la para a semana inteira (Salvar Semana Inteira).
4. Remover: Para limpar o plantão de um dia específico, basta clicar no botão [x] vermelho localizado no canto superior direito do dia correspondente no calendário.
