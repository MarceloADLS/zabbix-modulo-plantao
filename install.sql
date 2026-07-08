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
