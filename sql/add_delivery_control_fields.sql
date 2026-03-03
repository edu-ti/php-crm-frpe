-- sql/add_delivery_control_fields.sql
-- Adiciona a coluna data_entrega na tabela agendamentos caso não exista
ALTER TABLE agendamentos ADD COLUMN IF NOT EXISTS data_entrega DATE NULL DEFAULT NULL;
