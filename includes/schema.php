<?php

function quote_identifier(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}

function column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare("SHOW COLUMNS FROM " . quote_identifier($table) . " LIKE ?");
    $stmt->execute([$column]);

    return (bool)$stmt->fetch();
}

function ensure_column(PDO $pdo, string $table, string $column, string $definition): void
{
    if (!column_exists($pdo, $table, $column)) {
        $pdo->exec("ALTER TABLE " . quote_identifier($table) . " ADD COLUMN " . $definition);
    }
}

function unique_index_exists(PDO $pdo, string $table, array $columns): bool
{
    $stmt = $pdo->query("SHOW INDEX FROM " . quote_identifier($table));
    $indexes = [];

    foreach ($stmt->fetchAll() as $index) {
        if ((int)$index['Non_unique'] !== 0) {
            continue;
        }

        $indexes[$index['Key_name']][(int)$index['Seq_in_index']] = $index['Column_name'];
    }

    foreach ($indexes as $indexColumns) {
        ksort($indexColumns);

        if (array_values($indexColumns) === $columns) {
            return true;
        }
    }

    return false;
}

function ensure_unique_index(PDO $pdo, string $table, string $indexName, array $columns): void
{
    if (unique_index_exists($pdo, $table, $columns)) {
        return;
    }

    $columnSql = implode(', ', array_map('quote_identifier', $columns));
    $pdo->exec(
        "ALTER TABLE " . quote_identifier($table) .
        " ADD UNIQUE KEY " . quote_identifier($indexName) . " (" . $columnSql . ")"
    );
}

function ensure_schema(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS coaches (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nume VARCHAR(255) NOT NULL,
            specializare VARCHAR(255) NOT NULL,
            disponibilitate VARCHAR(255) NOT NULL,
            rol VARCHAR(255) NOT NULL,
            grupa_asignata VARCHAR(255) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS rooms (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nume VARCHAR(255) NOT NULL,
            capacitate INT NOT NULL,
            dotari TEXT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS activities (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_sala INT NOT NULL,
            nume_activitate VARCHAR(255) NOT NULL,
            data DATE NOT NULL,
            ora_start TIME NOT NULL,
            ora_end TIME NOT NULL,
            INDEX idx_activities_room_date (id_sala, data),
            CONSTRAINT fk_activities_room
                FOREIGN KEY (id_sala) REFERENCES rooms(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role VARCHAR(30) NOT NULL DEFAULT 'member',
            id_membru INT DEFAULT NULL,
            id_antrenor INT DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    ensure_column($pdo, 'users', 'role', "role VARCHAR(30) NOT NULL DEFAULT 'member'");
    ensure_column($pdo, 'users', 'id_membru', 'id_membru INT NULL');
    ensure_column($pdo, 'users', 'id_antrenor', 'id_antrenor INT NULL');
    $pdo->exec("ALTER TABLE users MODIFY role VARCHAR(30) NOT NULL DEFAULT 'member'");
    $pdo->exec("UPDATE users SET role = 'member' WHERE role = 'user'");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS members (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nume VARCHAR(255) NOT NULL,
            tip_membru ENUM('junior', 'senior', 'amator', 'pro') NOT NULL,
            nivel_joc VARCHAR(100),
            id_antrenor_asociat INT DEFAULT NULL,
            INDEX idx_members_coach (id_antrenor_asociat),
            CONSTRAINT fk_members_coach
                FOREIGN KEY (id_antrenor_asociat) REFERENCES coaches(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS competitions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nume VARCHAR(255) NOT NULL,
            data DATE NOT NULL,
            locatie VARCHAR(255) NOT NULL,
            tip VARCHAR(100) NOT NULL,
            INDEX idx_competitions_date (data)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    ensure_column($pdo, 'competitions', 'nume', 'nume VARCHAR(255) NULL');
    ensure_column($pdo, 'competitions', 'data', 'data DATE NULL');
    ensure_column($pdo, 'competitions', 'locatie', 'locatie VARCHAR(255) NULL');
    ensure_column($pdo, 'competitions', 'tip', 'tip VARCHAR(100) NULL');

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS competition_participants (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_competitie INT NOT NULL,
            id_membru INT NOT NULL,
            punctaj_obtinut DECIMAL(8,2) NOT NULL DEFAULT 0,
            UNIQUE KEY uniq_competition_member (id_competitie, id_membru),
            INDEX idx_participants_member (id_membru),
            CONSTRAINT fk_participants_competition
                FOREIGN KEY (id_competitie) REFERENCES competitions(id) ON DELETE CASCADE,
            CONSTRAINT fk_participants_member
                FOREIGN KEY (id_membru) REFERENCES members(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    ensure_column($pdo, 'competition_participants', 'id_competitie', 'id_competitie INT NULL');
    ensure_column($pdo, 'competition_participants', 'id_membru', 'id_membru INT NULL');
    ensure_column($pdo, 'competition_participants', 'punctaj_obtinut', 'punctaj_obtinut DECIMAL(8,2) NOT NULL DEFAULT 0');

    if (column_exists($pdo, 'competition_participants', 'id')) {
        $pdo->exec("
            DELETE duplicate_participants
            FROM competition_participants duplicate_participants
            INNER JOIN competition_participants kept_participants
                ON kept_participants.id_competitie = duplicate_participants.id_competitie
               AND kept_participants.id_membru = duplicate_participants.id_membru
               AND kept_participants.id < duplicate_participants.id
        ");
    }

    ensure_unique_index($pdo, 'competition_participants', 'uniq_competition_member', ['id_competitie', 'id_membru']);

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS awards (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nume VARCHAR(255) NOT NULL,
            descriere TEXT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    ensure_column($pdo, 'awards', 'nume', 'nume VARCHAR(255) NULL');
    ensure_column($pdo, 'awards', 'descriere', 'descriere TEXT NULL');

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS member_awards (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_award INT NOT NULL,
            id_membru INT NOT NULL,
            id_competitie INT DEFAULT NULL,
            data_acordare DATE NOT NULL,
            INDEX idx_member_awards_member (id_membru),
            INDEX idx_member_awards_competition (id_competitie),
            CONSTRAINT fk_member_awards_award
                FOREIGN KEY (id_award) REFERENCES awards(id) ON DELETE CASCADE,
            CONSTRAINT fk_member_awards_member
                FOREIGN KEY (id_membru) REFERENCES members(id) ON DELETE CASCADE,
            CONSTRAINT fk_member_awards_competition
                FOREIGN KEY (id_competitie) REFERENCES competitions(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    ensure_column($pdo, 'member_awards', 'id_award', 'id_award INT NULL');
    ensure_column($pdo, 'member_awards', 'id_membru', 'id_membru INT NULL');
    ensure_column($pdo, 'member_awards', 'id_competitie', 'id_competitie INT NULL');
    ensure_column($pdo, 'member_awards', 'data_acordare', 'data_acordare DATE NULL');

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS travel_expenses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            destinatie VARCHAR(255) NOT NULL,
            data DATE NOT NULL,
            scop VARCHAR(255) NOT NULL,
            cost_transport DECIMAL(10,2) NOT NULL DEFAULT 0,
            cost_cazare DECIMAL(10,2) NOT NULL DEFAULT 0,
            cost_masa DECIMAL(10,2) NOT NULL DEFAULT 0,
            total DECIMAL(10,2) NOT NULL DEFAULT 0,
            INDEX idx_travel_expenses_date (data)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    ensure_column($pdo, 'travel_expenses', 'destinatie', 'destinatie VARCHAR(255) NULL');
    ensure_column($pdo, 'travel_expenses', 'data', 'data DATE NULL');
    ensure_column($pdo, 'travel_expenses', 'scop', 'scop VARCHAR(255) NULL');
    ensure_column($pdo, 'travel_expenses', 'cost_transport', 'cost_transport DECIMAL(10,2) NOT NULL DEFAULT 0');
    ensure_column($pdo, 'travel_expenses', 'cost_cazare', 'cost_cazare DECIMAL(10,2) NOT NULL DEFAULT 0');
    ensure_column($pdo, 'travel_expenses', 'cost_masa', 'cost_masa DECIMAL(10,2) NOT NULL DEFAULT 0');
    ensure_column($pdo, 'travel_expenses', 'total', 'total DECIMAL(10,2) NOT NULL DEFAULT 0');

    if (column_exists($pdo, 'travel_expenses', 'id_travel')) {
        $pdo->exec("ALTER TABLE travel_expenses MODIFY id_travel INT NULL");
    }

    if (column_exists($pdo, 'travel_expenses', 'nume_participant')) {
        $pdo->exec("ALTER TABLE travel_expenses MODIFY nume_participant VARCHAR(255) NULL");
    }
}
