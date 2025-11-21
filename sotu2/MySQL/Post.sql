CREATE TABLE Post (
    post_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    media_url VARCHAR(255),
    content_text TEXT,
    created_at DATE,
    visibility ENUM('public', 'friends') DEFAULT 'public',
   
    CONSTRAINT fk_post_user
        FOREIGN KEY (user_id) REFERENCES User(user_id)
);
