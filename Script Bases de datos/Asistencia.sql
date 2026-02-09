-- Crear base de datos
CREATE DATABASE IF NOT EXISTS Asistencia
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE Asistencia;

-- Tabla empresas
CREATE TABLE empresas (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(191) NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla proyectos
CREATE TABLE proyectos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    empresa_id BIGINT UNSIGNED NOT NULL,
    nombre VARCHAR(191) NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_empresa_id (empresa_id),
    KEY idx_nombre (nombre),
    CONSTRAINT fk_proyectos_empresas
        FOREIGN KEY (empresa_id) REFERENCES empresas(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla tecnicos
-- nivel: 1=N1, 2=N2, 3=N3
CREATE TABLE tecnicos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(191) NOT NULL,
    nivel TINYINT NOT NULL,
    activo TINYINT NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_nivel (nivel),
    KEY idx_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Relación técnico <-> empresa (solo N2/N3)
CREATE TABLE tecnico_empresa (
    tecnico_id BIGINT UNSIGNED NOT NULL,
    empresa_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (tecnico_id, empresa_id),
    KEY idx_empresa_id (empresa_id),
    CONSTRAINT fk_tecnico_empresa_tecnico
        FOREIGN KEY (tecnico_id) REFERENCES tecnicos(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_tecnico_empresa_empresa
        FOREIGN KEY (empresa_id) REFERENCES empresas(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Relación técnico <-> proyecto (opcional)
CREATE TABLE tecnico_proyecto (
    tecnico_id BIGINT UNSIGNED NOT NULL,
    proyecto_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (tecnico_id, proyecto_id),
    KEY idx_proyecto_id (proyecto_id),
    CONSTRAINT fk_tecnico_proyecto_tecnico
        FOREIGN KEY (tecnico_id) REFERENCES tecnicos(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_tecnico_proyecto_proyecto
        FOREIGN KEY (proyecto_id) REFERENCES proyectos(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
