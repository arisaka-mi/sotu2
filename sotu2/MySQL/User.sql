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