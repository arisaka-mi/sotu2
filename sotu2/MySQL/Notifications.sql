CREATE TABLE Notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,        -- 通知を受け取る側
    from_user_id INT NOT NULL,   -- アクションした人
    type VARCHAR(20) NOT NULL,   -- like / comment / follow
    post_id INT NULL,            -- 投稿に紐づく通知ならセット
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_read TINYINT(1) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES User(user_id) ON DELETE CASCADE,
    FOREIGN KEY (from_user_id) REFERENCES User(user_id) ON DELETE CASCADE
);
