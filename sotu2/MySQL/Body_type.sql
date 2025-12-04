create table Body_type (
    bt_id varchar(50) PRIMARY KEY, 
    bt_name varchar(50), 
    bt_num int(10)
);

INSERT INTO `body_type` (`bt_id`, `bt_name`, `bt_num`) VALUES ('BT1', '骨格ストレート', NULL), ('BT2', '骨格ウェーブ', NULL), ('BT3', '骨格ナチュラル', NULL);