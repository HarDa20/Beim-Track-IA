-- =====================================================================
-- BEIM Track IA · Migración 002 (MySQL 8 / MariaDB 10.4+)
-- Spec 013 · Foto de perfil: agrega la columna usuarios.foto_perfil.
--
-- Úsala SOLO si ya tenías la base beimtrack_ia creada con una versión
-- anterior. Si vas a importar database/schema.sql desde cero, no hace falta.
-- Antes de ejecutar: phpMyAdmin > Exportar (respaldo).
-- =====================================================================
USE beimtrack_ia;

ALTER TABLE usuarios
    ADD COLUMN foto_perfil VARCHAR(80) DEFAULT NULL AFTER ultimo_acceso;
