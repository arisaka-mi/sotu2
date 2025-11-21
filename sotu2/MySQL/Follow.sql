CREATE TABLE Follow (
    follow_id INT AUTO_INCREMENT PRIMARY KEY,
    follower_id INT NOT NULL,
    followed_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (follower_id) REFERENCES User(user_id) ON DELETE CASCADE,
    FOREIGN KEY (followed_id) REFERENCES User(user_id) ON DELETE CASCADE,

    UNIQUE KEY (follower_id, followed_id)
);