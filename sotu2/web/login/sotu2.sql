create table User (
    user_id int(11) PRIMARY KEY, 
    bt_id varchar(50) FOREIGN KEY, 
    pc_id varchar(50) FOREIGN KEY,
    email varchar(100), 
    pwd varchar(255),  
    u_name varchar(50),  
    u_name_id varchar(50), 
    pro_img varchar(255), 
    hight int(3)
);

create table Body_type (
    bt_id varchar(50) , 
    bt_name varchar(50), 
    bt_num int(10)
);

create table Parsonal_color (
    pc_id varchar(50),
    pc_name varchar(50),
    pc_num int(10)
)