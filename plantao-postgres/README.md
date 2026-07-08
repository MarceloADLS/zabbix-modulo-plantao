# Módulo de Plantão (PostgreSQL) — Zabbix 7.4

M�dulo customizado para o frontend do Zabbix 7.4 que serve como fonte única da verdade visual para a monitoração: o time de monitoração usa este módulo para consultar o calendário de plantão, adicionar, alterar ou remover os plantonistas (Principal e Reserva) de cada dia do mês, e gerenciar os telefones de contato de cada técnico.

O módulo é isolado por equipe através dos Grupos de Usuários nativos do Zabbix (`usrgrp`), e roda nativamente sobre **PostgreSQL** — sem dependências de automações externas ou integrações de discagem.

---

## 🚀 Funcionalidades

* **Filtro por equipe** — cada calendário e cada lista de telefones é isolada por Grupo de Usuários do Zabbix.
* **Escala flexível por dia** — adicionar, alterar ou remover o plantonista (Principal ou Reserva) de um dia específico.
* **Escala em massa por semana** — replica a dupla de técnicos (Principal + Reserva) de segunda a domingo com um clique.
* **Remoção individual** — botão `[x]` em cada dia do calendário remove a escala daquele dia.
* **Telefones por técnico** — cada usuário do grupo tem um telefone de contato cadastrado, exibido no calendário junto ao nome do plantonista escalado.

Este módulo **não** dispara ligações, não integra com discadores e não altera media types do Zabbix. O escopo é estritamente salvar dados no banco e exibir a tela — toda automação de chamada foi removida.

---

## 📋 Requisitos

* **Zabbix:** versão 7.4.x (frontend PHP)
* **Banco de dados:** PostgreSQL 13+
* **PHP:** 8.2 ou superior (compatível com o Zabbix 7.4)
* **Permissões:** usuário com perfil de Admin ou Super Admin no Zabbix para gerenciar as escalas.

---

## 🛠️ Estrutura de banco de dados (PostgreSQL)

O módulo utiliza duas tabelas customizadas no banco do Zabbix. As chaves primárias usam `SERIAL` (equivalente ao `AUTO_INCREMENT` do MySQL) e as chaves estrangeiras para `usrgrpid`/`userid` são mapeadas como `BIGINT`, compatíveis com o schema nativo do Zabbix.

Se o módulo estiver habilitado pela interface do Zabbix (**Administration → General → Modules**), as tabelas já são criadas automaticamente pelo método `onInstall()` de `Module.php`. Para implantações manuais, ou para recriar o ambiente do zero, use o script abaixo (também disponível em `install.sql`):

```sql
-- =====================================================================
-- Módulo de Plantão (PostgreSQL) — Script de criação das tabelas
-- Compatível com o schema do Zabbix 7.4 rodando sobre PostgreSQL.
-- Execute com o mesmo usuário/role usado pelo frontend do Zabbix.
-- =====================================================================

-- Telefone de contato por técnico (userid é a chave única).
CREATE TABLE IF NOT EXISTS module_plantao_phones (
    userid BIGINT NOT NULL,
    phone  VARCHAR(100) NOT NULL DEFAULT '',
    PRIMARY KEY (userid)
);

-- Escala de plantão: um registro por equipe / dia / papel (principal ou reserva).
CREATE TABLE IF NOT EXISTS module_plantao_schedule (
    scheduleid    SERIAL NOT NULL,
    usrgrpid      BIGINT NOT NULL,
    userid        BIGINT NOT NULL,
    role_type     VARCHAR(20) NOT NULL,
    schedule_date DATE NOT NULL,
    created_by    BIGINT NOT NULL DEFAULT 0,
    created_at    INTEGER NOT NULL DEFAULT 0,
    PRIMARY KEY (scheduleid),
    CONSTRAINT module_plantao_schedule_uniq UNIQUE (usrgrpid, schedule_date, role_type)
);

-- Índices auxiliares para acelerar as consultas do calendário mensal.
CREATE INDEX IF NOT EXISTS idx_module_plantao_schedule_group_date
    ON module_plantao_schedule (usrgrpid, schedule_date);
```

### Sobre os `UPSERT`s

O PostgreSQL não possui o comando `REPLACE INTO` do MySQL. Todas as gravações do módulo usam `INSERT ... ON CONFLICT ... DO UPDATE`:

* **Escala** (`PlantaoSave.php`) — conflito tratado na chave única `(usrgrpid, schedule_date, role_type)`:
  ```sql
  ON CONFLICT (usrgrpid, schedule_date, role_type) DO UPDATE SET
      userid = EXCLUDED.userid,
      created_by = EXCLUDED.created_by,
      created_at = EXCLUDED.created_at
  ```
* **Telefones** (`PhonesSave.php`) — conflito tratado na chave única `userid` (um telefone por técnico):
  ```sql
  ON CONFLICT (userid) DO UPDATE SET
      phone = EXCLUDED.phone
  ```

Todas as datas usadas nas consultas são passadas pela função nativa `zbx_dbstr()` do Zabbix, garantindo o escaping correto independentemente do driver de banco configurado.

---

## 📦 Instalação do módulo

1. Copie a pasta do módulo para o diretório de módulos do frontend do seu Zabbix:
   ```
   /usr/share/zabbix/ui/modules/plantao/
   ```
2. Acesse a interface web do Zabbix como Super Admin.
3. Vá em **Administration → General → Modules**.
4. Clique em **Scan directory**.
5. Localize **Módulo de Plantão (PostgreSQL)** na lista e clique em **Enable**.
6. O menu **Plantão** aparecerá na barra lateral esquerda, com os submenus **Escala** e **Telefones**.

> Ao habilitar o módulo, o Zabbix executa `onInstall()` automaticamente, criando as tabelas via `CREATE TABLE IF NOT EXISTS`. Se preferir provisionar o banco antecipadamente (por exemplo, em pipelines de infraestrutura), rode o script SQL da seção anterior diretamente no PostgreSQL.

---

## 📝 Como utilizar

1. **Cadastrar telefones:** acesse **Plantão → Telefones**, selecione o time no filtro superior e informe o telefone de cada técnico listado. O telefone fica vinculado ao `userid` do técnico e aparece automaticamente no calendário sempre que ele estiver escalado.
2. **Planejar escala:** acesse **Plantão → Escala**, selecione o time. Clique no dia desejado no calendário e escolha o Técnico Principal e o Reserva no painel inferior.
3. **Salvar:** escolha entre aplicar a escala apenas para o dia selecionado (**Salvar Apenas Este Dia**) ou expandi-la para a semana inteira (**Salvar Semana Inteira**, de segunda a domingo).
4. **Remover:** para limpar o plantão de um dia específico, clique no botão `[x]` vermelho no canto superior direito do dia correspondente no calendário.

---

## 🗂️ Estrutura do projeto

```
plantao-postgres/
├── Module.php                 # Registro do menu e criação das tabelas (onInstall)
├── manifest.json              # Definição do módulo e mapeamento de actions
├── install.sql                # Script DDL standalone para PostgreSQL
├── actions/
│   ├── PlantaoList.php        # Carrega o calendário do mês/equipe selecionados
│   ├── PlantaoSave.php        # Grava a escala (dia ou semana) via INSERT ... ON CONFLICT
│   ├── PlantaoDelete.php      # Remove a escala de um dia específico
│   ├── PhonesList.php         # Carrega os telefones cadastrados dos técnicos da equipe
│   └── PhonesSave.php         # Grava o telefone do técnico via INSERT ... ON CONFLICT
├── views/
│   ├── plantao.list.php       # Tela do calendário mensal
│   └── phones.list.php        # Tela de cadastro de telefones por técnico
└── assets/css/icon.css        # Ícone do menu lateral
```

---

## 🔒 Escopo e permissões

* Todas as actions exigem perfil **Admin** ou **Super Admin** do Zabbix (`checkPermissions()`).
* O módulo não envia nem recebe dados de sistemas externos — toda a leitura e escrita é feita diretamente nas tabelas `module_plantao_schedule` e `module_plantao_phones` do próprio banco do Zabbix.
