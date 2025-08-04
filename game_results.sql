CREATE TABLE game_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    player_name VARCHAR(100) NOT NULL,
    result ENUM('win', 'lose', 'draw', 'none') NOT NULL,
    played_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
