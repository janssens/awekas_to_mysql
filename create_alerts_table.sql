CREATE TABLE IF NOT EXISTS weather_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    alert_key VARCHAR(50) NOT NULL COMMENT 'The weather measurement key (temperature, humidity, etc.)',
    alert_type ENUM('goes_below', 'goes_above') NOT NULL,
    threshold_value DECIMAL(10,2) NOT NULL COMMENT 'The reference value to trigger the alert',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_triggered_at DATETIME NULL COMMENT 'When the alert was last triggered',
    notification_cooldown INT NOT NULL DEFAULT 3600 COMMENT 'Minimum seconds between notifications for this alert',
    alert_message VARCHAR(255) NOT NULL COMMENT 'Custom message to show when alert is triggered',
    INDEX idx_active_key (is_active, alert_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 