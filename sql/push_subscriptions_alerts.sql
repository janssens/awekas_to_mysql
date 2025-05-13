-- Table pour stocker les souscriptions aux alertes spécifiques
CREATE TABLE IF NOT EXISTS push_subscription_alerts (
    subscription_id INT NOT NULL,
    alert_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (subscription_id, alert_id),
    FOREIGN KEY (subscription_id) REFERENCES push_subscriptions(id) ON DELETE CASCADE,
    FOREIGN KEY (alert_id) REFERENCES weather_alerts(id) ON DELETE CASCADE
); 