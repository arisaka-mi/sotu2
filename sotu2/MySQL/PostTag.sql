CREATE TABLE PostTag (
    post_id INT(11) NOT NULL,
    tag_id INT(11) NOT NULL,

    PRIMARY KEY (post_id, tag_id),

    CONSTRAINT fk_posttag_post
        FOREIGN KEY (post_id) REFERENCES Post(post_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_posttag_tag
        FOREIGN KEY (tag_id) REFERENCES Tag(tag_id)
        ON DELETE CASCADE
);
