CREATE TABLE User (
    user_id INT(11) PRIMARY KEY,
    bt_id VARCHAR(50),
    pc_id VARCHAR(50),
    email VARCHAR(100),
    pwd VARCHAR(255),
    u_name VARCHAR(50),
    u_name_id VARCHAR(50),
    pro_img VARCHAR(255),
    height INT(3),
    FOREIGN KEY (bt_id) REFERENCES AnotherTable(bt_id),
    FOREIGN KEY (pc_id) REFERENCES AnotherTable(pc_id)
);


create table Body_type (
    bt_id varchar(50) PRIMARY KEY, 
    bt_name varchar(50), 
    bt_num int(10)
);

create table Parsonal_color (
    pc_id varchar(50) PRIMARY KEY,
    pc_name varchar(50),
    pc_num int(10)
);

CREATE TABLE Follow (
    follow_id INT AUTO_INCREMENT PRIMARY KEY,
    follower_id INT NOT NULL,
    followed_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (follower_id) REFERENCES User(user_id) ON DELETE CASCADE,
    FOREIGN KEY (followed_id) REFERENCES User(user_id) ON DELETE CASCADE,

    UNIQUE KEY (follower_id, followed_id)
);
